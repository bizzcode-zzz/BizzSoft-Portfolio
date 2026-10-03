<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationReviewController extends Controller
{
    public function requestRevision(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::ReadyForReview,
            422,
            'You can only request a revision when the customization is ready for review.'
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use (
            $request,
            $customizationRequest,
            $validated
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::ReadyForReview,
                422,
                'You can only request a revision when the customization is ready for review.'
            );

            $lockedRequest->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::RevisionRequested,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }

    public function approve(
        Request $request,
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::ReadyForReview,
            422,
            'You can only approve a customization that is ready for review.'
        );

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
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

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::ReadyForReview,
                422,
                'You can only approve a customization that is ready for review.'
            );

            $quote = $lockedRequest
                ->quote()
                ->lockForUpdate()
                ->first();

            abort_unless(
                $quote
                && $entitlements->customizationQuoteIsFullyPaidAndUnheld($quote),
                422,
                'This customization cannot be completed while its payment is incomplete or under an active hold.'
            );

            $finalMessage = trim((string) ($validated['message'] ?? ''));

            if ($finalMessage !== '') {
                $lockedRequest->messages()->create([
                    'user_id' => $request->user()->id,
                    'message' => $finalMessage,
                ]);
            }

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::Completed,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }
}
