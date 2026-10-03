<?php

namespace App\Payments;

use App\Enums\CustomizationRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Data\CheckoutSession;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class CheckoutReservations
{
    /** @return array{Payment, bool} Payment and permission for this request to dispatch once. */
    public function reserve(Order $order, int $userId, string $provider, PaymentNumberGenerator $numbers): array
    {
        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException('Checkout must start outside a database transaction.');
        }

        return DB::transaction(function () use ($order, $userId, $provider, $numbers) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($order->user_id === $userId, 404);
            abort_unless(in_array($order->status, [OrderStatus::Pending, OrderStatus::AwaitingPayment], true),
                422, 'This order is not currently awaiting payment.');

            $verified = (float) $order->payments()->where('status', PaymentStatus::Verified->value)->sum('amount');
            $remaining = max((float) $order->price_snapshot - $verified, 0);
            abort_if($remaining <= 0, 422, 'This order has already been fully paid.');

            // Inspect durable reservations regardless of incidental webhook status changes.
            $blocking = $order->payments()->where('provider', $provider)->lockForUpdate()->get()
                ->filter(function (Payment $payment) {
                    $state = data_get($payment->metadata, 'checkout_reservation.state');

                    return in_array($state, ['creating', 'ready', 'outcome_unknown'], true)
                        || in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)
                        // Legacy "failed" creates may have lost the provider response.
                        || ($payment->status === PaymentStatus::Failed && $state !== 'confirmed_failed');
                });

            abort_if($blocking->count() > 1, 409, 'Multiple checkout attempts require reconciliation.');

            if ($payment = $blocking->first()) {
                return [$payment, false];
            }

            $payment = $order->payments()->create([
                'payment_number' => $numbers->generate(),
                'user_id' => $userId,
                'amount' => number_format($remaining, 2, '.', ''),
                'currency' => 'USD',
                'provider' => $provider,
                'status' => PaymentStatus::Pending,
                'metadata' => [
                    'source' => 'order_checkout',
                    'checkout_reservation' => [
                        'token' => (string) Str::uuid(),
                        'state' => 'creating',
                        'started_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

            return [$payment, true];
        });
    }

    /** @return array{Payment, bool} Payment and permission for this request to dispatch once. */
    public function reserveCustomizationQuote(
        CustomizationQuote $quote,
        int $userId,
        string $provider,
        PaymentNumberGenerator $numbers
    ): array {
        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException(
                'Checkout must start outside a database transaction.'
            );
        }

        return DB::transaction(function () use (
            $quote,
            $userId,
            $provider,
            $numbers
        ) {
            $customizationRequest = CustomizationRequest::query()
                ->whereKey($quote->customization_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $customizationRequest->user_id === $userId,
                404
            );

            abort_unless(
                $customizationRequest->status ===
                    CustomizationRequestStatus::Accepted,
                422,
                'This customization quotation is not awaiting payment.'
            );

            $quote = CustomizationQuote::query()
                ->whereKey($quote->getKey())
                ->where(
                    'customization_request_id',
                    $customizationRequest->id
                )
                ->lockForUpdate()
                ->firstOrFail();

            $verified = (float) $quote
                ->payments()
                ->where(
                    'status',
                    PaymentStatus::Verified->value
                )
                ->sum('amount');

            $remaining = max(
                (float) $quote->price - $verified,
                0
            );

            abort_if(
                $remaining <= 0,
                422,
                'This customization quotation has already been fully paid.'
            );

            $blocking = $quote
                ->payments()
                ->where('provider', $provider)
                ->lockForUpdate()
                ->get()
                ->filter(function (Payment $payment) {
                    $state = data_get(
                        $payment->metadata,
                        'checkout_reservation.state'
                    );

                    return in_array(
                        $state,
                        [
                            'creating',
                            'ready',
                            'outcome_unknown',
                        ],
                        true
                    )
                        || in_array(
                            $payment->status,
                            [
                                PaymentStatus::Pending,
                                PaymentStatus::Processing,
                            ],
                            true
                        )
                        || (
                            $payment->status === PaymentStatus::Failed
                            && $state !== 'confirmed_failed'
                        );
                });

            abort_if(
                $blocking->count() > 1,
                409,
                'Multiple checkout attempts require reconciliation.'
            );

            if ($payment = $blocking->first()) {
                return [$payment, false];
            }

            $payment = $quote->payments()->create([
                'payment_number' => $numbers->generate(),
                'user_id' => $userId,
                'amount' => number_format(
                    $remaining,
                    2,
                    '.',
                    ''
                ),
                'currency' => 'USD',
                'provider' => $provider,
                'status' => PaymentStatus::Pending,
                'metadata' => [
                    'source' => 'customization_quote_checkout',
                    'checkout_reservation' => [
                        'token' => (string) Str::uuid(),
                        'state' => 'creating',
                        'started_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

            return [$payment, true];
        });
    }

    public function finalize(Payment $reservation, CheckoutSession $checkout, ?Closure $validate = null): Payment
    {
        return DB::transaction(function () use ($reservation, $checkout, $validate) {
            $payment = $this->lockReservation($reservation);
            if ($validate) {
                $validate($payment);
            }

            if ($checkout->provider !== $payment->provider || trim($checkout->providerPaymentId) === '') {
                throw new RuntimeException('Provider identity does not match the reservation.');
            }

            if ($payment->provider_payment_id !== null && $payment->provider_payment_id !== $checkout->providerPaymentId) {
                throw new RuntimeException('The reservation is already bound to another provider transaction.');
            }

            if (Payment::query()->where('provider', $payment->provider)
                ->where('provider_payment_id', $checkout->providerPaymentId)
                ->whereKeyNot($payment->getKey())->exists()) {
                throw new RuntimeException('The provider transaction is already bound to another payment.');
            }

            $metadata = $payment->metadata ?? [];
            // Do not replace metadata or status written by a webhook while the request was in flight.
            $metadata['checkout_url'] = $checkout->checkoutUrl;
            foreach ($checkout->metadata as $key => $value) {
                if (! in_array($key, ['checkout_reservation', 'paddle_webhook', 'checkout_url'], true)) {
                    $metadata[$key] = $value;
                }
            }
            $metadata['checkout_reservation']['state'] = filled($checkout->checkoutUrl) ? 'ready' : 'outcome_unknown';
            $payment->update(['provider_payment_id' => $checkout->providerPaymentId, 'metadata' => $metadata]);

            return $payment;
        });
    }

    public function fail(Payment $reservation, bool $confirmedFailure): void
    {
        DB::transaction(function () use ($reservation, $confirmedFailure) {
            $payment = $this->lockReservation($reservation);
            $metadata = $payment->metadata;
            // A concurrent reconciliation may already have recovered the transaction.
            if ($metadata['checkout_reservation']['state'] === 'ready') {
                return;
            }
            $confirmed = $confirmedFailure && $payment->provider_payment_id === null
                && $payment->status === PaymentStatus::Pending;
            $metadata['checkout_reservation']['state'] = $confirmed ? 'confirmed_failed' : 'outcome_unknown';
            $payment->metadata = $metadata;
            if ($confirmed) {
                $payment->status = PaymentStatus::Failed;
            }
            $payment->save();
        });
    }

    private function lockReservation(Payment $reservation): Payment
    {
        $payment = Payment::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
        $expected = data_get($reservation->metadata, 'checkout_reservation.token');
        $actual = data_get($payment->metadata, 'checkout_reservation.token');

        if (! is_string($expected) || $expected === '' || $expected !== $actual
            || ! in_array(data_get($payment->metadata, 'checkout_reservation.state'), ['creating', 'outcome_unknown', 'ready'], true)
            || $payment->provider !== $reservation->provider
            || $payment->payable_type !== $reservation->payable_type
            || $payment->payable_id !== $reservation->payable_id
            || $payment->user_id !== $reservation->user_id
            || $payment->payment_number !== $reservation->payment_number
            || $payment->amount !== $reservation->amount
            || $payment->currency !== $reservation->currency) {
            throw new RuntimeException('Checkout reservation changed; reconciliation is required.');
        }

        return $payment;
    }
}
