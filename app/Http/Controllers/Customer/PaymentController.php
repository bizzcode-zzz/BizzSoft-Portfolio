<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentNumberGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class PaymentController extends Controller
{
    public function showOrder(
        Request $request,
        Order $order,
        PaymentGateway $gateway
    ): Response {
        $verifiedAmount = $this->verifiedAmountForOrder(
            $request,
            $order
        );

        $payments = $order
            ->payments()
            ->latest()
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'provider' => $payment->provider,
                'method' => $payment->method,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'created_at' => $payment->created_at,
            ]);

        return Inertia::render('Customer/Payments/Order', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
            ],

            'verified_amount' => number_format(
                (float) $verifiedAmount,
                2,
                '.',
                ''
            ),

            'remaining_amount' => number_format(
                max(
                    (float) $order->price_snapshot
                    - (float) $verifiedAmount,
                    0
                ),
                2,
                '.',
                ''
            ),

            'checkout_provider' => $gateway->provider(),

            'payments' => $payments,
        ]);
    }

    public function createOrderCheckout(
        Request $request,
        Order $order,
        PaymentGateway $gateway,
        PaymentNumberGenerator $paymentNumbers
    ): HttpResponse {
        $verifiedAmount = $this->verifiedAmountForOrder(
            $request,
            $order
        );

        $remainingAmount = max(
            (float) $order->price_snapshot
            - (float) $verifiedAmount,
            0
        );

        abort_if(
            $remainingAmount <= 0,
            422,
            'This order has already been fully paid.'
        );

        $existingPayment = $order
            ->payments()
            ->where('provider', $gateway->provider())
            ->whereIn('status', [
                PaymentStatus::Pending->value,
                PaymentStatus::Processing->value,
            ])
            ->latest()
            ->first();

        $existingCheckoutUrl = data_get(
            $existingPayment?->metadata,
            'checkout_url'
        );

        if (
            is_string($existingCheckoutUrl) &&
            trim($existingCheckoutUrl) !== ''
        ) {
            return Inertia::location(
                $existingCheckoutUrl
            );
        }

        $payment = $order->payments()->create([
            'payment_number' => $paymentNumbers->generate(),
            'user_id' => $request->user()->id,
            'amount' => number_format(
                $remainingAmount,
                2,
                '.',
                ''
            ),
            'currency' => 'USD',
            'provider' => $gateway->provider(),
            'method' => null,
            'status' => PaymentStatus::Pending,
            'metadata' => [
                'source' => 'order_checkout',
            ],
        ]);

        try {
            $checkout = $gateway->createCheckout(
                $payment,
                $this->checkoutDescriptionForOrder($order)
            );

            $payment->update([
                'provider_payment_id' =>
                    $checkout->providerPaymentId,

                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    $checkout->metadata,
                    [
                        'checkout_url' =>
                            $checkout->checkoutUrl,
                    ]
                ),
            ]);

            return Inertia::location(
                $checkout->checkoutUrl
            );
        } catch (Throwable $exception) {
            report($exception);

            $payment->update([
                'status' => PaymentStatus::Failed,
                'notes' =>
                    'Payment checkout could not be created.',
            ]);

            abort(
                502,
                'Unable to start payment checkout. Please try again.'
            );
        }
    }

    private function verifiedAmountForOrder(
        Request $request,
        Order $order
    ): float {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        abort_unless(
            in_array(
                $order->status,
                [
                    OrderStatus::Pending,
                    OrderStatus::AwaitingPayment,
                ],
                true
            ),
            422,
            'This order is not currently awaiting payment.'
        );

        $verifiedAmount = (float) $order
            ->payments()
            ->where(
                'status',
                PaymentStatus::Verified->value
            )
            ->sum('amount');

        abort_if(
            $verifiedAmount >=
                (float) $order->price_snapshot,
            422,
            'This order has already been fully paid.'
        );

        return $verifiedAmount;
    }

    private function checkoutDescriptionForOrder(
        Order $order
    ): string {
        $version = trim(
            (string) $order->product_version_snapshot
        );

        $product = trim(
            (string) $order->product_name_snapshot
        );

        if ($version !== '') {
            $normalizedVersion = ltrim(
                $version,
                'vV'
            );

            if (
                ! str_ends_with(
                    strtolower($product),
                    ' v'.strtolower($normalizedVersion)
                )
            ) {
                $product .= ' v'.$normalizedVersion;
            }
        }

        return sprintf(
            '%s - Order %s',
            $product,
            $order->order_number
        );
    }
}
