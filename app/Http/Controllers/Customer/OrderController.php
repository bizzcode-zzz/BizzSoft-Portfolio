<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->latest('ordered_at')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'ordered_at' => $order->ordered_at,
            ]);

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function store(
        Request $request,
        Product $product
    ): RedirectResponse {
        abort_unless(
            $product->status === ProductStatus::Active,
            404
        );

        $existingOrder = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->latest('ordered_at')
            ->first();

        if ($existingOrder) {
            return redirect()
                ->route('customer.orders.show', $existingOrder)
                ->with(
                    'info',
                    'You already have an active order for this product.'
                );
        }

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'user_id' => $request->user()->id,
            'product_id' => $product->id,

            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,

            'status' => OrderStatus::Pending,
            'ordered_at' => now(),
        ]);

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Your order has been created successfully.');
    }

    public function show(
        Request $request,
        Order $order
    ): Response {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        return Inertia::render('Customer/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'product_id' => $order->product_id,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'notes' => $order->notes,
                'ordered_at' => $order->ordered_at,
                'created_at' => $order->created_at,
            ],
        ]);
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = sprintf(
                'BS-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (
            Order::query()
                ->where('order_number', $orderNumber)
                ->exists()
        );

        return $orderNumber;
    }
}