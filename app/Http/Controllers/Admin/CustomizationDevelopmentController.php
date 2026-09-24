<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;

class CustomizationDevelopmentController extends Controller
{
    public function start(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::Accepted,
            422,
            'Only accepted customization requests can start development.'
        );

        abort_unless(
            $customizationRequest->quote()->exists(),
            422,
            'A quotation is required before development can start.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    public function readyForReview(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::InProgress,
            422,
            'Only customization requests in progress can be marked ready for review.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    public function resume(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::RevisionRequested,
            422,
            'Only customization requests with requested revisions can resume development.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }
}