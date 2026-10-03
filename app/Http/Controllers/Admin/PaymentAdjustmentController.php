<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAdjustment;
use App\Payments\AdjustmentPurchaseLocks;
use App\Payments\Providers\Paddle\PaddleAdjustmentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentAdjustmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/PaymentAdjustments/Index', [
            'adjustments' => PaymentAdjustment::query()->orderByDesc('review_required')
                ->orderByDesc('id')->paginate(25),
        ]);
    }

    public function show(PaymentAdjustment $paymentAdjustment): Response
    {
        return Inertia::render('Admin/PaymentAdjustments/Show', ['adjustment' => $paymentAdjustment]);
    }

    public function update(Request $request, PaymentAdjustment $paymentAdjustment, PaddleAdjustmentProcessor $processor, AdjustmentPurchaseLocks $locks): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['review', 'suspend', 'restore'])],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'last_event_id' => ['required', 'string'],
            'history_count' => ['required', 'integer', 'min:0'],
        ]);
        $locks->transaction(function () use ($request, $paymentAdjustment, $processor, $validated): void {
            $hint = PaymentAdjustment::query()->findOrFail($paymentAdjustment->id);

            $snapshot = $hint->snapshot;
            $requiredSnapshotKeys = [
                'transaction_id',
                'customer_id',
                'currency',
                'totals_currency',
                'action',
                'status',
            ];

            if (
                ! is_array($snapshot)
                || array_diff($requiredSnapshotKeys, array_keys($snapshot))
            ) {
                throw ValidationException::withMessages([
                    'reason' => 'Stored provider evidence is incomplete. Reload or reconcile this adjustment before recording a decision.',
                ]);
            }

            $payments = $processor->lockPurchase($hint->provider_transaction_id, $hint);
            $payment = $processor->matchedPayment($snapshot, $payments);
            $ledger = PaymentAdjustment::query()->whereKey($hint->id)->lockForUpdate()->firstOrFail();
            $processor->assertAssociation($ledger, $hint);
            if ($ledger->last_event_id !== $validated['last_event_id']
                || count($ledger->history) !== (int) $validated['history_count']
                || $ledger->snapshot !== $hint->snapshot) {
                throw ValidationException::withMessages(['reason' => 'An update arrived. Reload and review it before deciding.']);
            }
            $action = $validated['action'];
            if ($action === 'suspend') {
                $hasConflict = collect($ledger->history)->contains(
                    fn ($entry) => ($entry['disposition'] ?? null) === 'conflicting_event'
                );

                $expectedOrderId = $payment?->payable_type === Order::class
                    ? (int) $payment->payable_id
                    : null;

                $ledgerOrderId = $ledger->order_id === null
                    ? null
                    : (int) $ledger->order_id;

                if (! $payment || $hasConflict || $ledger->snapshot['status'] !== 'approved'
                    || ! in_array($ledger->snapshot['action'], ['refund', 'chargeback', 'chargeback_warning'], true)
                    || ($ledger->payment_id !== null && $ledgerOrderId !== $expectedOrderId)
                    || ($ledger->payment_id !== null && (int) $ledger->payment_id !== $payment->id)) {
                    throw ValidationException::withMessages(['reason' => 'Suspension requires a trusted purchase match and an effective adjustment. Missing or conflicting identity must be reconciled first.']);
                }

                $ledger->payment_id = $payment->id;
                $ledger->order_id = $expectedOrderId;
            }
            if ($action === 'restore' && ! $ledger->hold_active) {
                throw ValidationException::withMessages(['reason' => 'This adjustment has no active hold to restore.']);
            }
            $history = $ledger->history;
            $history[] = [
                'kind' => 'admin', 'admin_id' => $request->user()->id,
                'action' => $action, 'reason' => $validated['reason'],
                'at' => now()->toIso8601String(), 'event_id' => $ledger->last_event_id,
                'hold_before' => $ledger->hold_active,
            ];
            $ledger->fill([
                'history' => $history,
                'hold_active' => match ($action) {
                    'suspend' => true, 'restore' => false, default => $ledger->hold_active,
                },
                'review_required' => $action === 'review', 'decision' => 'admin_'.$action,
            ])->save();
        });

        return redirect()->route('admin.payment-adjustments.show', $paymentAdjustment)
            ->with('success', 'Decision recorded. Other payment holds and manual license revocations still apply.');
    }
}
