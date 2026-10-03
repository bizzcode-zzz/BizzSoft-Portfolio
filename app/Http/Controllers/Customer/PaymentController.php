<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use App\Models\Order;
use App\Payments\CheckoutReservations;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\CheckoutNotDispatched;
use App\Payments\Exceptions\CheckoutProviderRejected;
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
        PaymentNumberGenerator $paymentNumbers,
        CheckoutReservations $reservations
    ): HttpResponse {
        [$payment, $mayDispatch] = $reservations->reserve(
            $order, $request->user()->id, $gateway->provider(), $paymentNumbers
        );

        if (! $mayDispatch) {
            $url = data_get($payment->metadata, 'checkout_url');
            $state = data_get($payment->metadata, 'checkout_reservation.state');
            if (($state === null || $state === 'ready') && filled($url)
                && in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
                return Inertia::location($url);
            }

            abort(409, 'Checkout is being prepared or requires reconciliation. No new payment was created.');
        }

        // The reservation has committed. No database transaction spans this provider call.
        try {
            $checkout = $gateway->createCheckout($payment, $this->checkoutDescriptionForOrder($payment->payable));
        } catch (Throwable $exception) {
            report($exception);

            $confirmedFailure =
                $exception instanceof CheckoutNotDispatched
                || $exception instanceof CheckoutProviderRejected;

            $reservations->fail($payment, $confirmedFailure);

            abort(
                502,
                match (true) {
                    $exception instanceof CheckoutNotDispatched =>
                        'Checkout could not be started. Please try again.',
                    $exception instanceof CheckoutProviderRejected =>
                        'Checkout was rejected by the payment provider. Please contact support before trying again.',
                    default =>
                        'Checkout outcome is uncertain. Please contact support; do not submit another payment.',
                }
            );
        }

        // If this save fails, the durable creating reservation still prevents a second POST.
        $payment = $reservations->finalize($payment, $checkout);
        abort_unless(filled(data_get($payment->metadata, 'checkout_url')), 409,
            'Checkout requires reconciliation. No new payment will be created.');

        return Inertia::location($payment->metadata['checkout_url']);
    }

    public function createCustomizationCheckout(
        Request $request,
        CustomizationRequest $customizationRequest,
        PaymentGateway $gateway,
        PaymentNumberGenerator $paymentNumbers,
        CheckoutReservations $reservations
    ): HttpResponse {
        $this->authorize('update', $customizationRequest);

        $quote = $customizationRequest
            ->quote()
            ->first();

        abort_unless(
            $quote,
            422,
            'This customization request does not have a quotation.'
        );

        [$payment, $mayDispatch] =
            $reservations->reserveCustomizationQuote(
                $quote,
                $request->user()->id,
                $gateway->provider(),
                $paymentNumbers
            );

        if (! $mayDispatch) {
            $url = data_get(
                $payment->metadata,
                'checkout_url'
            );

            $state = data_get(
                $payment->metadata,
                'checkout_reservation.state'
            );

            if (
                ($state === null || $state === 'ready')
                && filled($url)
                && in_array(
                    $payment->status,
                    [
                        PaymentStatus::Pending,
                        PaymentStatus::Processing,
                    ],
                    true
                )
            ) {
                return Inertia::location($url);
            }

            abort(
                409,
                'Checkout is being prepared or requires reconciliation. No new payment was created.'
            );
        }

        try {
            $checkout = $gateway->createCheckout(
                $payment,
                sprintf(
                    'BizzSoft Customization - Request #%d',
                    $customizationRequest->id
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            $confirmedFailure =
                $exception instanceof CheckoutNotDispatched
                || $exception instanceof CheckoutProviderRejected;

            $reservations->fail(
                $payment,
                $confirmedFailure
            );

            abort(
                502,
                match (true) {
                    $exception instanceof CheckoutNotDispatched =>
                        'Checkout could not be started. Please try again.',
                    $exception instanceof CheckoutProviderRejected =>
                        'Checkout was rejected by the payment provider. Please contact support before trying again.',
                    default =>
                        'Checkout outcome is uncertain. Please contact support; do not submit another payment.',
                }
            );
        }

        $payment = $reservations->finalize(
            $payment,
            $checkout
        );

        abort_unless(
            filled(data_get(
                $payment->metadata,
                'checkout_url'
            )),
            409,
            'Checkout requires reconciliation. No new payment will be created.'
        );

        return Inertia::location(
            $payment->metadata['checkout_url']
        );
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
