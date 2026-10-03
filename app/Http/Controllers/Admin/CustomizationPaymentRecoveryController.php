<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use App\Models\Payment;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomizationPaymentRecoveryController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $customizationRequest,
            $entitlements,
            $validated
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            if (
                $lockedRequest->status !==
                CustomizationRequestStatus::Cancelled
            ) {
                throw ValidationException::withMessages([
                    'reason' =>
                        'Only a cancelled customization can use payment recovery.',
                ]);
            }

            $quote = $lockedRequest
                ->quote()
                ->lockForUpdate()
                ->first();

            if ($quote === null) {
                throw ValidationException::withMessages([
                    'reason' =>
                        'Recovery requires a quotation with verified payment.',
                ]);
            }

            $verifiedPayments = $quote
                ->payments()
                ->where(
                    'status',
                    PaymentStatus::Verified->value
                )
                ->where(
                    'user_id',
                    $lockedRequest->user_id
                )
                ->where(
                    'currency',
                    'USD'
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $usablePayments = $verifiedPayments
                ->reject(
                    fn (Payment $payment) =>
                        $entitlements->paymentIsHeld($payment->id)
                )
                ->values();

            $usableMinorUnits = $usablePayments->sum(
                fn (Payment $payment) =>
                    $this->toMinorUnits((string) $payment->amount)
            );

            $quoteMinorUnits = $this->toMinorUnits(
                (string) $quote->price
            );

            if (
                $usablePayments->isEmpty()
                || $usableMinorUnits < $quoteMinorUnits
            ) {
                throw ValidationException::withMessages([
                    'reason' =>
                        'Recovery requires fully verified, unheld USD payment for this customer and quotation.',
                ]);
            }

            $audit = sprintf(
                '[Late-payment recovery %s] Admin #%s moved Cancelled to Accepted. Verified usable payments: %s. Reason: %s',
                now()->toIso8601String(),
                $request->user()->id,
                $usablePayments
                    ->pluck('payment_number')
                    ->implode(', '),
                $validated['reason']
            );

            $lockedRequest->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $audit,
            ]);

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::Accepted,
            ]);
        }, 3);

        return redirect()
            ->route(
                'admin.customizations.show',
                $customizationRequest
            )
            ->with(
                'success',
                'Paid customization recovered to Accepted. Start development separately after review.'
            );
    }

    private function toMinorUnits(string $amount): int
    {
        $amount = trim($amount);

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )
        ) {
            throw new InvalidArgumentException(
                'Payment amount must be a valid USD amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) $fraction;
    }
}