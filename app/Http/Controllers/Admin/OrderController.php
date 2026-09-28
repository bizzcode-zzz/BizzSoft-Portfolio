<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductOwnership;
use App\Models\ProductRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()
            ->with('user:id,name,email')
            ->latest('ordered_at')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user->name,
                'customer_email' => $order->user->email,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'ordered_at' => $order->ordered_at,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load('user:id,name,email');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,

                'customer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],

                'product_id' => $order->product_id,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,

                'status' => $order->status->value,
                'status_label' => $order->status->label(),

                'notes' => $order->notes,
                'ordered_at' => $order->ordered_at,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ],

            'statuses' => $this->statuses($order),
        ]);
    }

    public function updateStatus(
        Request $request,
        Order $order
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::enum(OrderStatus::class),
            ],
        ]);

        $targetStatus = OrderStatus::from(
            $validated['status']
        );

        DB::transaction(function () use (
            $request,
            $order,
            $targetStatus
        ): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->status->canAdminTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => 'This order status transition is not allowed.',
                ]);
            }

            if ($targetStatus === OrderStatus::Completed) {
                $this->completeOrder(
                    $request,
                    $lockedOrder
                );

                return;
            }

            $lockedOrder->update([
                'status' => $targetStatus,
            ]);
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    private function completeOrder(
        Request $request,
        Order $order
    ): void {
        $verifiedAmount = (string) $order
            ->payments()
            ->where(
                'status',
                PaymentStatus::Verified->value
            )
            ->where('user_id', $order->user_id)
            ->where('currency', 'USD')
            ->sum('amount');

        if (
            $this->toMinorUnits($verifiedAmount) <
            $this->toMinorUnits(
                (string) $order->price_snapshot
            )
        ) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot be completed until payment is fully verified.',
            ]);
        }

        $startingReleaseId = ProductRelease::query()
            ->where('product_id', $order->product_id)
            ->where(
                'status',
                ProductReleaseStatus::Published->value
            )
            ->latest('released_at')
            ->latest('id')
            ->value('id');

        ProductOwnership::create([
            'user_id' => $order->user_id,
            'product_id' => $order->product_id,
            'order_id' => $order->id,
            'starting_release_id' => $startingReleaseId,
            'granted_by' => $request->user()->id,
            'granted_at' => now(),
        ]);

        $order->update([
            'status' => OrderStatus::Completed,
        ]);
    }

    private function statuses(Order $order): array
    {
        return collect([
            $order->status,
            ...$order->status->adminTransitionTargets(),
        ])
            ->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    private function toMinorUnits(string $amount): int
    {
        $amount = trim($amount);

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $fraction = str_pad(
            substr($fraction, 0, 2),
            2,
            '0'
        );

        return ((int) $whole * 100) + (int) $fraction;
    }
}
