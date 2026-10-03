<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductLicense;
use App\Models\ProductOwnership;
use App\Models\ProductRelease;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ->withSum($this->verifiedPaymentRelation(), 'amount')
            ->latest('ordered_at')
            ->latest('id')
            ->paginate(25)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user->name,
                'customer_email' => $order->user->email,
                'product_name' => $order->product_name_snapshot,
                'product_version' => $order->product_version_snapshot,
                'price' => $order->price_snapshot,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'payment_review' => $this->paymentReview($order),
                'ordered_at' => $order->ordered_at,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load('user:id,name,email');
        $order->loadSum($this->verifiedPaymentRelation(), 'amount');

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
                'payment_review' => $this->paymentReview($order),

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

    public function recoverPaidCancellation(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);

        DB::transaction(function () use ($request, $order, $validated): void {
            // Coordinate with order creation before reopening a cancelled purchase.
            $order->user()->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::Cancelled) {
                throw ValidationException::withMessages(['reason' => 'Only a cancelled order can use payment recovery.']);
            }

            $payments = $lockedOrder->payments()->orderBy('id')->lockForUpdate()->get()
                ->filter(fn ($payment) => $payment->status === PaymentStatus::Verified
                    && (int) $payment->user_id === (int) $lockedOrder->user_id && $payment->currency === 'USD');

            if (app(PaymentEntitlements::class)->orderIsHeld($lockedOrder->id)) {
                throw ValidationException::withMessages(['reason' => 'This purchase has an active payment hold. Review the adjustment before recovery.']);
            }

            $verifiedAmount = (string) $payments->sum('amount');
            if ($payments->isEmpty() || $this->toMinorUnits($verifiedAmount) <= 0
                || $this->toMinorUnits($verifiedAmount) < $this->toMinorUnits((string) $lockedOrder->price_snapshot)) {
                throw ValidationException::withMessages(['reason' => 'Recovery requires fully verified USD payment for this customer and order.']);
            }

            if (Order::query()->where('user_id', $lockedOrder->user_id)
                ->where('product_id', $lockedOrder->product_id)
                ->whereKeyNot($lockedOrder->id)
                ->where('status', '!=', OrderStatus::Cancelled->value)->exists()) {
                throw ValidationException::withMessages(['reason' => 'Another active order exists for this product. Review it before recovering this order.']);
            }

            $audit = sprintf(
                '[Late-payment recovery %s] Admin #%s moved Cancelled to Processing. Verified payments: %s. Reason: %s',
                now()->toIso8601String(),
                $request->user()->id,
                $payments->pluck('payment_number')->implode(', '),
                $validated['reason'],
            );
            $lockedOrder->update([
                'status' => OrderStatus::Processing,
                'notes' => trim(($lockedOrder->notes ?? '')."\n\n".$audit),
            ]);
        }, 3);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Paid order recovered to Processing. Complete fulfillment separately.');
    }

    private function verifiedPaymentRelation(): array
    {
        return ['payments as verified_amount' => fn ($query) => $query
            ->where('status', PaymentStatus::Verified->value)
            ->where('currency', 'USD')
            ->whereColumn('payments.user_id', 'orders.user_id')];
    }

    private function paymentReview(Order $order): array
    {
        $verified = $this->toMinorUnits((string) ($order->verified_amount ?? '0'));
        $needsReview = $order->status === OrderStatus::Cancelled && $verified > 0;
        $held = app(PaymentEntitlements::class)->orderIsHeld($order->id);

        return [
            'needs_review' => $needsReview,
            'verified_amount' => number_format($verified / 100, 2, '.', ''),
            'payment_hold' => $held,
            'can_recover' => ! $held && $needsReview && $verified >= $this->toMinorUnits((string) $order->price_snapshot),
        ];
    }

    private function completeOrder(
        Request $request,
        Order $order
    ): void {
        $payments = $order->payments()->orderBy('id')->lockForUpdate()->get();
        if (app(PaymentEntitlements::class)->orderIsHeld($order->id)) {
            throw ValidationException::withMessages(['status' => 'This purchase has an active payment hold. Review the adjustment before completion.']);
        }
        $verifiedAmount = (string) $payments->filter(fn ($payment) => $payment->status === PaymentStatus::Verified
            && (int) $payment->user_id === (int) $order->user_id && $payment->currency === 'USD')->sum('amount');

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

        $ownership = ProductOwnership::query()->firstOrCreate(
            [
                'user_id' => $order->user_id,
                'product_id' => $order->product_id,
            ],
            [
                'order_id' => $order->id,
                'starting_release_id' => $startingReleaseId,
                'granted_by' => $request->user()->id,
                'granted_at' => now(),
            ]
        );

        ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => $this->generateLicenseKey(),
            'status' => 'unactivated',
        ]);

        $order->update([
            'status' => OrderStatus::Completed,
        ]);
    }

    private function generateLicenseKey(): string
    {
        do {
            $token = strtoupper(Str::random(16));
            $licenseKey = 'BIZZ-'.implode('-', str_split($token, 4));
        } while (
            ProductLicense::query()
                ->where('license_key', $licenseKey)
                ->exists()
        );

        return $licenseKey;
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
