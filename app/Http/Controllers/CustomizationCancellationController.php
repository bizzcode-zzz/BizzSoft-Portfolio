<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CustomizationCancellationController extends Controller
{
    public function cancel(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        DB::transaction(function () use ($customizationRequest): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            $allowedStatuses = [
                CustomizationRequestStatus::Submitted,
                CustomizationRequestStatus::UnderReview,
                CustomizationRequestStatus::NeedsInformation,
                CustomizationRequestStatus::QuoteSent,
                CustomizationRequestStatus::Accepted,
            ];

            abort_unless(
                in_array($lockedRequest->status, $allowedStatuses, true),
                422,
                'This customization request can no longer be cancelled.'
            );

            $quote = $lockedRequest
                ->quote()
                ->lockForUpdate()
                ->first();

            if ($quote !== null) {
                $payments = $quote
                    ->payments()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $hasVerifiedPayment = $payments->contains(
                    fn (Payment $payment) =>
                        $payment->status === PaymentStatus::Verified
                );

                abort_if(
                    $hasVerifiedPayment,
                    422,
                    'This customization has a verified payment and can no longer be cancelled. Contact BizzSoft for payment review.'
                );

                $hasCheckoutAtRisk = $payments->contains(
                    function (Payment $payment): bool {
                        if ($payment->provider !== 'paddle') {
                            return false;
                        }

                        if ($payment->status === PaymentStatus::Cancelled) {
                            return false;
                        }

                        $state = data_get(
                            $payment->metadata,
                            'checkout_reservation.state'
                        );

                        return in_array(
                            $state,
                            [
                                'creating',
                                'ready',
                                'outcome_unknown',
                            ],
                            true
                        )
                            || in_array(
                                $payment->status,
                                [
                                    PaymentStatus::Pending,
                                    PaymentStatus::Processing,
                                ],
                                true
                            )
                            || (
                                $payment->status === PaymentStatus::Failed
                                && $state !== 'confirmed_failed'
                            );
                    }
                );

                abort_if(
                    $hasCheckoutAtRisk,
                    422,
                    'A Paddle checkout is active or requires reconciliation. This customization cannot be cancelled until the payment outcome is known.'
                );
            }

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::Cancelled,
            ]);
        }, 3);

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }
}