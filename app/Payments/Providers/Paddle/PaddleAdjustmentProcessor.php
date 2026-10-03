<?php

namespace App\Payments\Providers\Paddle;

use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\PaymentWebhookEvent;
use App\Payments\AdjustmentPurchaseLocks;
use App\Payments\Exceptions\AdjustmentAttributionChanged;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Paddle\SDK\Notifications\Events\AdjustmentCreated;
use Paddle\SDK\Notifications\Events\AdjustmentUpdated;
use Throwable;

final class PaddleAdjustmentProcessor
{
    public function __construct(private AdjustmentPurchaseLocks $locks) {}

    public function process(AdjustmentCreated|AdjustmentUpdated $event): bool
    {
        PaymentWebhookEvent::query()->insertOrIgnore([
            'provider' => 'paddle', 'event_id' => $event->eventId,
            'event_type' => $event->eventType->getValue(),
            'provider_payment_id' => $event->adjustment->transactionId,
            'occurred_at' => $event->occurredAt, 'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            return $this->locks->transaction(function () use ($event): bool {
                $webhook = PaymentWebhookEvent::query()->where('provider', 'paddle')
                    ->where('event_id', $event->eventId)->lockForUpdate()->firstOrFail();
                if ($webhook->processed_at !== null) {
                    return false;
                }
                $a = $event->adjustment;
                $snapshot = $this->snapshot($event);
                $hint = PaymentAdjustment::query()->where('provider', 'paddle')
                    ->where('provider_adjustment_id', $a->id)->first();
                $payments = $this->lockPurchase($a->transactionId, $hint);
                $payment = $this->matchedPayment($snapshot, $payments);

                // No ledger row is inserted or locked until shared order/payment locks are held.
                PaymentAdjustment::query()->insertOrIgnore([
                    'provider' => 'paddle', 'provider_adjustment_id' => $a->id,
                    'provider_transaction_id' => $a->transactionId, 'last_event_id' => $event->eventId,
                    'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'history' => '[]',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $ledger = PaymentAdjustment::query()->where('provider', 'paddle')
                    ->where('provider_adjustment_id', $a->id)->lockForUpdate()->firstOrFail();
                $this->assertAssociation($ledger, $hint);
                $this->apply($ledger, $event, $snapshot, $payment);
                $webhook->update([
                    'processed_at' => now(), 'failed_at' => null, 'failure_message' => null,
                    'metadata' => ['adjustment_id' => $ledger->id, 'decision' => $ledger->decision],
                ]);

                return true;
            });
        } catch (Throwable $exception) {
            PaymentWebhookEvent::query()->where('provider', 'paddle')->where('event_id', $event->eventId)
                ->whereNull('processed_at')->update([
                    'failed_at' => now(), 'failure_message' => Str::limit($exception->getMessage(), 4000),
                ]);
            throw $exception;
        }
    }

    public function lockPurchase(string $transactionId, ?PaymentAdjustment $hint): Collection
    {
        return $this->locks->payments(
            array_values(array_unique(array_filter([$transactionId, $hint?->provider_transaction_id]))),
            $hint?->payment_id ? [(int) $hint->payment_id] : [],
        );
    }

    public function assertAssociation(PaymentAdjustment $ledger, ?PaymentAdjustment $hint): void
    {
        $association = fn (PaymentAdjustment $row) => [
            $row->provider_transaction_id, $row->payment_id, $row->order_id,
        ];
        if (($hint && $association($ledger) !== $association($hint))
            || (! $hint && ($ledger->payment_id !== null || $ledger->order_id !== null))) {
            throw new AdjustmentAttributionChanged('Ledger attribution changed; restart the whole transaction.');
        }
    }

    // Receives already locked/re-queried payments. Never acquires purchase locks after a ledger lock.
    public function matchedPayment(array $s, Collection $lockedPayments): ?Payment
    {
        $payments = $lockedPayments->filter(
            fn (Payment $p) => $p->provider === 'paddle'
                && $p->provider_payment_id === $s['transaction_id']
        );

        if ($payments->count() !== 1) {
            return null;
        }

        $p = $payments->first();
        $m = $p->metadata ?? [];
        $provenance = $m['paddle_customer_identity_provenance'] ?? [];

        if (
            $p->status !== PaymentStatus::Verified
            || ! in_array(
                $p->payable_type,
                [Order::class, CustomizationQuote::class],
                true
            )
            || $p->currency !== 'USD'
            || $s['currency'] !== 'USD'
            || $s['totals_currency'] !== 'USD'
            || empty($m['paddle_customer_id'])
            || $m['paddle_customer_id'] !== $s['customer_id']
            || ! empty($m['paddle_customer_identity_review_required'])
            || ($provenance['transaction_id'] ?? null) !== $p->provider_payment_id
            || ($provenance['event_type'] ?? null) !== 'transaction.completed'
            || empty($provenance['event_id'])
        ) {
            return null;
        }

        $evidence = PaymentWebhookEvent::query()
            ->where('provider', 'paddle')
            ->where('event_id', $provenance['event_id'])
            ->where('event_type', 'transaction.completed')
            ->where('provider_payment_id', $p->provider_payment_id)
            ->whereNotNull('processed_at')
            ->first();

        if (
            ! $evidence
            || (string) ($evidence->metadata['payment_id'] ?? '') !== (string) $p->id
            || ($evidence->metadata['payment_number'] ?? null) !== $p->payment_number
        ) {
            return null;
        }

        // Use only relations populated from the rows locked by lockPurchase().
        $payable = $p->getRelation('payable');

        if ($p->payable_type === Order::class) {
            if (
                ! ($payable instanceof Order)
                || (int) $payable->user_id !== (int) $p->user_id
                || (string) $payable->price_snapshot !== (string) $p->amount
                || $lockedPayments
                    ->filter(
                        fn (Payment $candidate) =>
                            $candidate->payable_type === Order::class
                            && (int) $candidate->payable_id === (int) $p->payable_id
                            && $candidate->status === PaymentStatus::Verified
                    )
                    ->count() !== 1
            ) {
                return null;
            }

            return $p;
        }

        if (! ($payable instanceof CustomizationQuote)) {
            return null;
        }

        $request = $payable->getRelation('customizationRequest');

        if (
            ! $request
            || (int) $request->user_id !== (int) $p->user_id
            || $this->paymentMinorUnits((string) $p->amount) === null
        ) {
            return null;
        }

        return $p;
    }

    private function apply(PaymentAdjustment $ledger, AdjustmentCreated|AdjustmentUpdated $event, array $s, ?Payment $payment): void
    {
        $a = $event->adjustment;
        $version = CarbonImmutable::instance($a->updatedAt ?? $a->createdAt);
        $history = $ledger->history;
        $history[] = [
            'kind' => 'provider', 'event_id' => $event->eventId,
            'event_type' => $event->eventType->getValue(), 'occurred_at' => $event->occurredAt->format(DATE_ATOM),
            'provider_updated_at' => $version->format('Y-m-d\TH:i:s.uP'), 'snapshot' => $s,
        ];
        $old = $ledger->snapshot;
        $previous = $ledger->provider_updated_at;
        $identityChanged = $ledger->provider_transaction_id !== $a->transactionId
            || $old['customer_id'] !== $s['customer_id'] || $old['currency'] !== $s['currency'];
        if ($identityChanged || ($previous && $version->equalTo($previous) && $old !== $s)) {
            $history[array_key_last($history)]['disposition'] = 'conflicting_event';
            $ledger->update([
                'history' => $history, 'last_event_id' => $event->eventId,
                'provider_updated_at' => $previous && $previous->greaterThan($version) ? $previous : $version,
                'review_required' => true, 'decision' => 'conflicting_event',
            ]);

            return;
        }
        if ($previous && $version->lessThan($previous)) {
            $ledger->update(['history' => $history]);

            return;
        }
        if ($previous && $old === $s) {
            $ledger->update(['history' => $history, 'provider_updated_at' => $version, 'last_event_id' => $event->eventId]);

            return;
        }
        $manualOverride = collect($history)->contains(fn ($entry) => ($entry['kind'] ?? null) === 'admin'
            && in_array($entry['action'] ?? null, ['suspend', 'restore'], true));
        $conflicted = collect($history)->contains(fn ($entry) => ($entry['disposition'] ?? null) === 'conflicting_event');
        $decision = 'unmatched_or_untrusted';
        $review = true;
        $hold = $ledger->hold_active;
        if ($payment && ! $conflicted) {
            $expectedOrderId = $payment->payable_type === Order::class
                ? (int) $payment->payable_id
                : null;

            $ledgerOrderId = $ledger->order_id === null
                ? null
                : (int) $ledger->order_id;

            $hasExistingAttribution =
                $ledger->payment_id !== null
                || $ledger->order_id !== null;

            if (
                $hasExistingAttribution
                && (
                    (int) $ledger->payment_id !== $payment->id
                    || $ledgerOrderId !== $expectedOrderId
                )
            ) {
                $decision = 'conflicting_attribution';
            } else {
                $ledger->payment_id = $payment->id;
                $ledger->order_id = $expectedOrderId;
                $decision = $this->decision($s, $payment, $ledger);
                if ($manualOverride) {
                    $decision = 'provider_update_after_review';
                } elseif ($decision === 'automatic_hold') {
                    $hold = true;
                    $review = false;
                } elseif ($decision === 'no_effect' && ! $hold) {
                    $review = false;
                }
            }
        }
        $ledger->fill([
            'snapshot' => $s, 'history' => $history, 'provider_updated_at' => $version,
            'last_event_id' => $event->eventId, 'hold_active' => $hold, 'review_required' => $review,
            'decision' => $conflicted ? 'conflicting_event' : $decision,
        ])->save();
    }

    private function decision(array $s, Payment $payment, PaymentAdjustment $ledger): string
    {
        if (str_ends_with($s['action'], '_reverse') || $s['status'] === 'reversed') {
            return 'reversal_review';
        }
        if ($s['action'] === 'refund' && in_array($s['status'], ['pending_approval', 'rejected'], true)) {
            return 'no_effect';
        }
        if ($s['status'] !== 'approved' || ! in_array($s['action'], ['refund', 'chargeback', 'chargeback_warning'], true)) {
            return 'unsupported_adjustment';
        }
        $expected = $this->paymentMinorUnits((string) $payment->amount);
        if ($expected === null || $s['type'] !== 'full' || ! $this->validTotals($s)
            || (ltrim($s['total'], '0') ?: '0') !== $expected || $s['items'] === []) {
            return 'partial_tax_or_ambiguous';
        }
        $sum = 0;
        foreach ($s['items'] as $item) {
            if ($item['type'] !== 'full' || ! $this->validTotals($item)) {
                return 'partial_tax_or_ambiguous';
            }
            $sum += (int) $item['total'];
        }
        if ($sum !== (int) $s['total']) {
            return 'partial_tax_or_ambiguous';
        }
        // Current-read peers in ID order, after the shared purchase mutex; no later purchase locks.
        $others = PaymentAdjustment::query()->where('provider', 'paddle')
            ->where('provider_transaction_id', $s['transaction_id'])->whereKeyNot($ledger->id)
            ->orderBy('id')->sharedLock()->get();
        foreach ($others as $other) {
            if (($other->snapshot['status'] ?? null) === 'approved'
                && in_array($other->snapshot['action'] ?? null, ['refund', 'chargeback', 'chargeback_warning'], true)) {
                return 'cumulative_review';
            }
        }

        return 'automatic_hold';
    }

    private function paymentMinorUnits(string $amount): ?string
    {
        if (! preg_match('/^([0-9]{1,10})\.([0-9]{2})$/D', $amount, $matches)) {
            return null;
        }
        $minor = ltrim($matches[1].$matches[2], '0');

        return $minor === '' ? null : $minor;
    }

    private function validTotals(array $totals): bool
    {
        foreach (['subtotal', 'tax', 'total'] as $key) {
            if (! is_string($totals[$key]) || ! preg_match('/^[0-9]{1,12}$/D', $totals[$key])) {
                return false;
            }
        }

        return (int) $totals['subtotal'] > 0
            && (int) $totals['subtotal'] + (int) $totals['tax'] === (int) $totals['total'];
    }

    private function snapshot(AdjustmentCreated|AdjustmentUpdated $event): array
    {
        $a = $event->adjustment;

        return [
            'transaction_id' => $a->transactionId, 'customer_id' => $a->customerId,
            'currency' => $a->currencyCode->getValue(), 'totals_currency' => $a->totals->currencyCode->getValue(),
            'action' => $a->action->getValue(), 'status' => $a->status->getValue(), 'type' => $a->type?->getValue(),
            'subtotal' => $a->totals->subtotal, 'tax' => $a->totals->tax, 'total' => $a->totals->total, 'reason' => $a->reason,
            'items' => array_map(fn ($item) => [
                'id' => $item->id, 'item_id' => $item->itemId, 'type' => $item->type->getValue(),
                'subtotal' => $item->totals->subtotal, 'tax' => $item->totals->tax, 'total' => $item->totals->total,
            ], $a->items),
        ];
    }
}
