<?php

namespace App\Payments\Providers\Paddle;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Paddle\SDK\Entities\Event as PaddleEvent;
use Paddle\SDK\Notifications\Entities\Transaction as PaddleTransaction;
use Paddle\SDK\Notifications\Events\TransactionCanceled;
use Paddle\SDK\Notifications\Events\TransactionCompleted;
use Paddle\SDK\Notifications\Events\TransactionPaid;
use Paddle\SDK\Notifications\Events\TransactionPaymentFailed;
use RuntimeException;
use Throwable;

final class PaddleWebhookProcessor
{
    public function process(PaddleEvent $event): bool
    {
        $eventType = (string) $event->eventType->getValue();

        $transaction = $this->transactionFromEvent(
            $event
        );

        $providerPaymentId = $transaction?->id;

        PaymentWebhookEvent::query()->insertOrIgnore([
            'provider' => 'paddle',
            'event_id' => $event->eventId,
            'event_type' => $eventType,
            'provider_payment_id' => $providerPaymentId,
            'occurred_at' => $event->occurredAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            return DB::transaction(
                function () use (
                    $event,
                    $eventType,
                    $transaction,
                    $providerPaymentId
                ): bool {
                    $webhookEvent = PaymentWebhookEvent::query()
                        ->where('provider', 'paddle')
                        ->where('event_id', $event->eventId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($webhookEvent->processed_at !== null) {
                        return false;
                    }

                    if ($transaction === null) {
                        $webhookEvent->update([
                            'processed_at' => now(),
                            'failed_at' => null,
                            'failure_message' => null,
                            'metadata' => [
                                'ignored' => true,
                                'reason' =>
                                    'Unsupported Paddle event type.',
                            ],
                        ]);

                        return true;
                    }

                    $payments = Payment::query()
                        ->where('provider', 'paddle')
                        ->where(
                            'provider_payment_id',
                            $transaction->id
                        )
                        ->lockForUpdate()
                        ->get();

                    if ($payments->count() !== 1) {
                        throw new RuntimeException(
                            sprintf(
                                'Expected exactly one local payment for Paddle transaction [%s], found [%d].',
                                $transaction->id,
                                $payments->count()
                            )
                        );
                    }

                    $payment = $payments->first();

                    $this->validateTransaction(
                        $payment,
                        $transaction
                    );

                    match (true) {
                        $event instanceof TransactionCompleted =>
                            $this->handleCompleted(
                                $payment,
                                $transaction,
                                $event
                            ),

                        $event instanceof TransactionPaid =>
                            $this->handlePaid(
                                $payment,
                                $transaction,
                                $event
                            ),

                        $event instanceof TransactionPaymentFailed =>
                            $this->handleFailed(
                                $payment,
                                $transaction,
                                $event
                            ),

                        $event instanceof TransactionCanceled =>
                            $this->handleCancelled(
                                $payment,
                                $transaction,
                                $event
                            ),

                        default => null,
                    };

                    $webhookEvent->update([
                        'event_type' => $eventType,
                        'provider_payment_id' =>
                            $providerPaymentId,
                        'processed_at' => now(),
                        'failed_at' => null,
                        'failure_message' => null,
                        'metadata' => [
                            'payment_id' => $payment->id,
                            'payment_number' =>
                                $payment->payment_number,
                        ],
                    ]);

                    return true;
                },
                3
            );
        } catch (Throwable $exception) {
            PaymentWebhookEvent::query()
                ->where('provider', 'paddle')
                ->where('event_id', $event->eventId)
                ->whereNull('processed_at')
                ->update([
                    'failed_at' => now(),
                    'failure_message' => Str::limit(
                        $exception->getMessage(),
                        4000
                    ),
                    'updated_at' => now(),
                ]);

            throw $exception;
        }
    }

    private function transactionFromEvent(
        PaddleEvent $event
    ): ?PaddleTransaction {
        return match (true) {
            $event instanceof TransactionCompleted,
            $event instanceof TransactionPaid,
            $event instanceof TransactionPaymentFailed,
            $event instanceof TransactionCanceled =>
                $event->transaction,

            default => null,
        };
    }

    private function validateTransaction(
        Payment $payment,
        PaddleTransaction $transaction
    ): void {
        $currency = strtoupper(
            (string) $transaction
                ->currencyCode
                ->getValue()
        );

        if ($currency !== 'USD') {
            throw new InvalidArgumentException(
                "Unexpected Paddle currency [{$currency}]."
            );
        }

        if (
            strtoupper((string) $payment->currency) !==
            $currency
        ) {
            throw new InvalidArgumentException(
                'Paddle transaction currency does not match the local payment.'
            );
        }

        $expectedMinorUnits = $this->toMinorUnits(
            (string) $payment->amount
        );

        $providerTotal = ltrim(
            (string) $transaction
                ->details
                ->totals
                ->total,
            '0'
        );

        if ($providerTotal === '') {
            $providerTotal = '0';
        }

        if ($providerTotal !== $expectedMinorUnits) {
            throw new InvalidArgumentException(
                'Paddle transaction total does not match the local payment amount.'
            );
        }

        $customData = $this->customData(
            $transaction
        );

        $expected = [
            'bizzsoft_payment_id' =>
                (string) $payment->id,

            'payment_number' =>
                (string) $payment->payment_number,

            'payable_type' =>
                (string) $payment->payable_type,

            'payable_id' =>
                (string) $payment->payable_id,

            'user_id' =>
                (string) $payment->user_id,
        ];

        foreach ($expected as $key => $value) {
            if (
                ! array_key_exists($key, $customData) ||
                (string) $customData[$key] !== $value
            ) {
                throw new InvalidArgumentException(
                    "Paddle custom data mismatch for [{$key}]."
                );
            }
        }
    }

    private function handlePaid(
        Payment $payment,
        PaddleTransaction $transaction,
        PaddleEvent $event
    ): void {
        if ($payment->status !== PaymentStatus::Verified) {
            $payment->status =
                PaymentStatus::Processing;
        }

        $payment->metadata = $this->paymentMetadata(
            $payment,
            $transaction,
            $event
        );

        $payment->save();
    }

    private function handleCompleted(
        Payment $payment,
        PaddleTransaction $transaction,
        PaddleEvent $event
    ): void {
        $payment->status =
            PaymentStatus::Verified;

        $payment->verified_at =
            $event->occurredAt;

        $payment->verified_by = null;

        $payment->metadata = $this->paymentMetadata(
            $payment,
            $transaction,
            $event
        );

        $payment->save();

        $this->advanceOrderIfFullyPaid(
            $payment
        );
    }

    private function handleFailed(
        Payment $payment,
        PaddleTransaction $transaction,
        PaddleEvent $event
    ): void {
        if ($payment->status !== PaymentStatus::Verified) {
            $payment->status =
                PaymentStatus::Failed;
        }

        $payment->metadata = $this->paymentMetadata(
            $payment,
            $transaction,
            $event
        );

        $payment->save();
    }

    private function handleCancelled(
        Payment $payment,
        PaddleTransaction $transaction,
        PaddleEvent $event
    ): void {
        if ($payment->status !== PaymentStatus::Verified) {
            $payment->status =
                PaymentStatus::Cancelled;
        }

        $payment->metadata = $this->paymentMetadata(
            $payment,
            $transaction,
            $event
        );

        $payment->save();
    }

    private function advanceOrderIfFullyPaid(
        Payment $payment
    ): void {
        if ($payment->payable_type !== Order::class) {
            return;
        }

        $order = Order::query()
            ->whereKey($payment->payable_id)
            ->lockForUpdate()
            ->first();

        if ($order === null) {
            throw new RuntimeException(
                'The paid order no longer exists.'
            );
        }

        if (
            ! in_array(
                $order->status,
                [
                    OrderStatus::Pending,
                    OrderStatus::AwaitingPayment,
                ],
                true
            )
        ) {
            return;
        }

        $verifiedAmount = (string) $order
            ->payments()
            ->where(
                'status',
                PaymentStatus::Verified->value
            )
            ->sum('amount');

        if (
            (int) $this->toMinorUnits($verifiedAmount) <
            (int) $this->toMinorUnits(
                (string) $order->price_snapshot
            )
        ) {
            return;
        }

        $order->update([
            'status' => OrderStatus::Processing,
        ]);
    }

    private function paymentMetadata(
        Payment $payment,
        PaddleTransaction $transaction,
        PaddleEvent $event
    ): array {
        $metadata = $payment->metadata ?? [];

        $metadata['paddle_webhook'] = [
            'event_id' => $event->eventId,
            'event_type' =>
                (string) $event->eventType->getValue(),

            'occurred_at' =>
                $event->occurredAt->format(DATE_ATOM),

            'transaction_id' =>
                $transaction->id,

            'currency' =>
                (string) $transaction
                    ->currencyCode
                    ->getValue(),

            'subtotal_minor' =>
                $transaction
                    ->details
                    ->totals
                    ->subtotal,

            'total_minor' =>
                $transaction
                    ->details
                    ->totals
                    ->total,
        ];

        return $metadata;
    }

    private function customData(
        PaddleTransaction $transaction
    ): array {
        $data = $transaction->customData?->data ?? [];

        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return is_array($data)
            ? $data
            : [];
    }

    private function toMinorUnits(
        string $amount
    ): string {
        $amount = trim($amount);

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )
        ) {
            throw new InvalidArgumentException(
                'Payment amount must be a valid USD amount.'
            );
        }

        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0'
        );

        $minorUnits = ltrim(
            $whole.$decimal,
            '0'
        );

        return $minorUnits === ''
            ? '0'
            : $minorUnits;
    }
}
