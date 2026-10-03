<?php

namespace App\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\Order;
use App\Models\PaymentAdjustment;
use App\Models\ProductOwnership;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PaymentEntitlements
{
    public function orderIsHeld(int $orderId): bool
    {
        $query = PaymentAdjustment::query()
            ->where('order_id', $orderId)
            ->where('hold_active', true);

        if (DB::transactionLevel() > 0) {
            return $query
                ->orderBy('id')
                ->sharedLock()
                ->first(['id']) !== null;
        }

        return $query->exists();
    }

    public function paymentIsHeld(int $paymentId): bool
    {
        $query = PaymentAdjustment::query()
            ->where('payment_id', $paymentId)
            ->where('hold_active', true);

        if (DB::transactionLevel() > 0) {
            return $query
                ->orderBy('id')
                ->sharedLock()
                ->first(['id']) !== null;
        }

        return $query->exists();
    }

    public function customizationQuoteIsFullyPaidAndUnheld(
        CustomizationQuote $quote
    ): bool {
        $paymentsQuery = $quote
            ->payments()
            ->where('status', PaymentStatus::Verified->value)
            ->orderBy('id');

        if (DB::transactionLevel() > 0) {
            $paymentsQuery->lockForUpdate();
        }

        $payments = $paymentsQuery->get(['id', 'amount']);

        if ($payments->isEmpty()) {
            return false;
        }

        $adjustmentsQuery = PaymentAdjustment::query()
            ->whereIn('payment_id', $payments->pluck('id'))
            ->where('hold_active', true)
            ->orderBy('id');

        if (DB::transactionLevel() > 0) {
            $adjustmentsQuery->sharedLock();
        }

        $heldPaymentIds = $adjustmentsQuery
            ->pluck('payment_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $held = array_flip($heldPaymentIds);
        $verifiedMinor = 0;

        foreach ($payments as $payment) {
            if (isset($held[(int) $payment->id])) {
                continue;
            }

            $verifiedMinor += $this->moneyToMinorUnits(
                (string) $payment->amount
            );
        }

        return $verifiedMinor >= $this->moneyToMinorUnits(
            (string) $quote->price
        );
    }

    public function ownershipHasUnheldPurchase(
        ProductOwnership $ownership
    ): bool {
        $licenseOrderIds = $ownership
            ->licenses()
            ->whereIn('status', ['active', 'unactivated'])
            ->pluck('order_id');

        $orders = Order::query()
            ->where('user_id', $ownership->user_id)
            ->where('product_id', $ownership->product_id)
            ->where('status', OrderStatus::Completed->value)
            ->where(function ($query) use ($ownership, $licenseOrderIds) {
                $query
                    ->whereKey($ownership->order_id)
                    ->orWhereIn('id', $licenseOrderIds);
            })
            ->get();

        foreach ($orders as $order) {
            if ($this->orderIsHeld($order->id)) {
                continue;
            }

            // Preserve legacy grants without licenses, but never use a revoked
            // license as a fallback grant.
            if (
                $licenseOrderIds->contains($order->id)
                || (
                    (int) $ownership->order_id === $order->id
                    && ! $ownership->licenses()
                        ->where('order_id', $order->id)
                        ->exists()
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function moneyToMinorUnits(string $amount): int
    {
        if (! preg_match('/^([0-9]+)\.([0-9]{2})$/D', $amount, $matches)) {
            throw new InvalidArgumentException(
                'Stored payment amount is not a valid two-decimal monetary value.'
            );
        }

        return ((int) $matches[1] * 100) + (int) $matches[2];
    }
}