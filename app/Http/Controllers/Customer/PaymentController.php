<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function showOrder(
        Request $request,
        Order $order
    ): Response {
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

        $verifiedAmount = $order
            ->payments()
            ->where('status', PaymentStatus::Verified->value)
            ->sum('amount');

        abort_if(
            (float) $verifiedAmount >= (float) $order->price_snapshot,
            422,
            'This order has already been fully paid.'
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

            'payments' => $payments,
        ]);
    }
}