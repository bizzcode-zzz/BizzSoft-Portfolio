<?php

namespace App\Payments\Providers\Paddle;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Payments\AdjustmentPurchaseLocks;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Paddle\SDK\Entities\Event as PaddleEvent;
use Paddle\SDK\Notifications\Entities\Transaction as PaddleTransaction;
use Paddle\SDK\Notifications\Events\AdjustmentCreated;
use Paddle\SDK\Notifications\Events\AdjustmentUpdated;
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
        if ($event instanceof AdjustmentCreated || $event instanceof AdjustmentUpdated) {
            return app(PaddleAdjustmentProcessor::class)->process($event);
        }

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
            return app(AdjustmentPurchaseLocks::class)->transaction(
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
                                'reason' => 'Unsupported Paddle event type.',
                            ],
                        ]);

                        return true;
                    }

                    $payments = app(AdjustmentPurchaseLocks::class)->payments([$transaction->id])
                        ->filter(fn (Payment $payment) => $payment->provider === 'paddle'
                            && $payment->provider_payment_id === $transaction->id);

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
                        $event instanceof TransactionCompleted => $this->handleCompleted(
                            $payment,
                            $transaction,
                            $event
                        ),

                        $event instanceof TransactionPaid => $this->handlePaid(
                            $payment,
                            $transaction,
                            $event
                        ),

                        $event instanceof TransactionPaymentFailed => $this->handleFailed(
                            $payment,
                            $transaction,
                            $event
                        ),

                        $event instanceof TransactionCanceled => $this->handleCancelled(
                            $payment,
                            $transaction,
                            $event
                        ),

                        default => null,
                    };

                    $webhookEvent->update([
                        'event_type' => $eventType,
                        'provider_payment_id' => $providerPaymentId,
                        'processed_at' => now(),
                        'failed_at' => null,
                        'failure_message' => null,
                        'metadata' => [
                            'payment_id' => $payment->id,
                            'payment_number' => $payment->payment_number,
                        ],
                    ]);

                    return true;
                }
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
            $event instanceof TransactionCanceled => $event->transaction,

            default => null,
        };
    }

    private function validateTransaction(Payment $payment, PaddleTransaction $transaction): void
    {
        (new PaddleTransactionValidator)->validate($payment, $transaction);
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

        $metadata = $this->paymentMetadata(
            $payment,
            $transaction,
            $event
        );

        $customerId = $transaction->customerId;

        if (is_string($customerId) && trim($customerId) !== '') {
            $existingCustomerId = $metadata['paddle_customer_id'] ?? null;

            $provenance = [
                'transaction_id' => $transaction->id,
                'event_id' => $event->eventId,
                'event_type' => 'transaction.completed',
                'occurred_at' => $event->occurredAt->format(DATE_ATOM),
            ];

            if (
                $existingCustomerId !== null
                && $existingCustomerId !== $customerId
            ) {
                $metadata['paddle_customer_identity_review_required'] = true;
                $metadata['paddle_customer_identity_conflicts'][$event->eventId] = [
                    'customer_id' => $customerId,
                    ...$provenance,
                ];
            } else {
                $metadata['paddle_customer_id'] = $customerId;
                $metadata['paddle_customer_identity_provenance'] = $provenance;
            }
        }

        $payment->metadata = $metadata;

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
            'event_type' => (string) $event->eventType->getValue(),

            'occurred_at' => $event->occurredAt->format(DATE_ATOM),

            'transaction_id' => $transaction->id,

            'currency' => (string) $transaction
                ->currencyCode
                ->getValue(),

            'subtotal_minor' => $transaction
                ->details
                ->totals
                ->subtotal,

            'total_minor' => $transaction
                ->details
                ->totals
                ->total,
        ];

        return $metadata;
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
