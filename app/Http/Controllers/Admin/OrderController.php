<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

            'statuses' => $this->statuses(),
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

        $order->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    private function statuses(): array
    {
        return collect(OrderStatus::cases())
            ->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }
}