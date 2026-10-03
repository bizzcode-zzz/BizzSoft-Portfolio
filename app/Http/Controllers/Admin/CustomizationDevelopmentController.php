<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CustomizationDevelopmentController extends Controller
{
    public function start(
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        DB::transaction(function () use (
            $customizationRequest,
            $entitlements
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::Accepted,
                422,
                'Only accepted customization requests can start development.'
            );

            $this->ensurePaymentAllowsProgress(
                $lockedRequest,
                $entitlements,
                'The quotation must be fully paid with no active payment hold before development can start.'
            );

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::InProgress,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    public function readyForReview(
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        DB::transaction(function () use (
            $customizationRequest,
            $entitlements
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::InProgress,
                422,
                'Only customization requests in progress can be marked ready for review.'
            );

            $this->ensurePaymentAllowsProgress(
                $lockedRequest,
                $entitlements,
                'The quotation must remain fully paid with no active payment hold before delivery review.'
            );

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::ReadyForReview,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    public function resume(
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        DB::transaction(function () use (
            $customizationRequest,
            $entitlements
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::RevisionRequested,
                422,
                'Only customization requests with requested revisions can resume development.'
            );

            $this->ensurePaymentAllowsProgress(
                $lockedRequest,
                $entitlements,
                'The quotation must remain fully paid with no active payment hold before development can resume.'
            );

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::InProgress,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    private function ensurePaymentAllowsProgress(
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements,
        string $message
    ): void {
        $quote = $customizationRequest
            ->quote()
            ->lockForUpdate()
            ->first();

        abort_unless(
            $quote,
            422,
            'A quotation is required before this customization can progress.'
        );

        abort_unless(
            $entitlements->customizationQuoteIsFullyPaidAndUnheld($quote),
            422,
            $message
        );
    }
}