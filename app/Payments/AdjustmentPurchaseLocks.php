<?php

namespace App\Payments;

use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Exceptions\AdjustmentAttributionChanged;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AdjustmentPurchaseLocks
{
    public function transaction(callable $callback): mixed
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Adjustment transactions must start outside an ambient transaction.');
        }

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction($callback, 3);
            } catch (AdjustmentAttributionChanged $exception) {
                // DB::transaction has rolled back before we retry. No locks are retained.
                if ($attempt >= 3) {
                    throw $exception;
                }
            }
        }
    }

    public function payments(array $transactionIds, array $paymentIds = []): Collection
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Purchase locking requires a transaction.');
        }

        $query = fn () => Payment::query()->where(function ($query) use ($transactionIds, $paymentIds) {
            $query->where(function ($query) use ($transactionIds) {
                $query->where('provider', 'paddle')->whereIn('provider_payment_id', $transactionIds);
            })->orWhereIn('id', $paymentIds);
        })->orderBy('id');

        $candidates = $query()->get();

        $orderIds = $candidates
            ->where('payable_type', Order::class)
            ->pluck('payable_id')
            ->unique()
            ->sort()
            ->values();

        $quoteIds = $candidates
            ->where('payable_type', CustomizationQuote::class)
            ->pluck('payable_id')
            ->unique()
            ->sort()
            ->values();

        // Read quote ownership first so the parent customization request can be
        // locked before the quote itself, matching the normal customization flow.
        $quoteSnapshot = CustomizationQuote::query()
            ->whereIn('id', $quoteIds)
            ->orderBy('id')
            ->get(['id', 'customization_request_id']);

        $requestIds = $quoteSnapshot
            ->pluck('customization_request_id')
            ->unique()
            ->sort()
            ->values();

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $requests = CustomizationRequest::query()
            ->whereIn('id', $requestIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $quotes = CustomizationQuote::query()
            ->whereIn('id', $quoteIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $quoteFingerprint = fn ($rows) => $rows
            ->map(fn (CustomizationQuote $quote) => [
                $quote->id,
                $quote->customization_request_id,
            ])
            ->values()
            ->all();

        if ($quoteFingerprint($quoteSnapshot) !== $quoteFingerprint($quotes)) {
            throw new AdjustmentAttributionChanged(
                'Customization purchase attribution changed; restart the transaction.'
            );
        }

        // Lock ALL payments belonging to the affected purchases, including other
        // providers and newly verified attempts.
        $locked = Payment::query()
            ->where(function ($query) use ($orderIds, $quoteIds, $candidates) {
                $query->whereIn('id', $candidates->modelKeys())
                    ->orWhere(function ($query) use ($orderIds) {
                        $query->where('payable_type', Order::class)
                            ->whereIn('payable_id', $orderIds);
                    })
                    ->orWhere(function ($query) use ($quoteIds) {
                        $query->where('payable_type', CustomizationQuote::class)
                            ->whereIn('payable_id', $quoteIds);
                    });
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // Re-query the provider identity after the shared locks. A changed mapping
        // restarts the whole transaction.
        $current = $query()->lockForUpdate()->get();

        $fingerprint = fn (Collection $rows) => $rows
            ->map(fn (Payment $payment) => [
                $payment->id,
                $payment->provider,
                $payment->provider_payment_id,
                $payment->payable_type,
                $payment->payable_id,
                $payment->user_id,
            ])
            ->values()
            ->all();

        $lockedCandidates = $locked->whereIn('id', $candidates->modelKeys());

        if (
            $fingerprint($candidates) !== $fingerprint($lockedCandidates)
            || $fingerprint($lockedCandidates) !== $fingerprint($current)
        ) {
            throw new AdjustmentAttributionChanged(
                'Purchase attribution changed; restart the transaction.'
            );
        }

        foreach ($locked as $payment) {
            if ($payment->payable_type === Order::class) {
                $payment->setRelation(
                    'payable',
                    $orders->get($payment->payable_id)
                );

                continue;
            }

            if ($payment->payable_type === CustomizationQuote::class) {
                $quote = $quotes->get($payment->payable_id);

                if ($quote !== null) {
                    $quote->setRelation(
                        'customizationRequest',
                        $requests->get($quote->customization_request_id)
                    );
                }

                $payment->setRelation('payable', $quote);
            }
        }

        return $locked;
    }
}