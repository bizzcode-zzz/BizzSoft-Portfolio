<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
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
            $customizationRequest->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $customizationRequest->update([
                'status' => CustomizationRequestStatus::RevisionRequested,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }

    public function approve(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::ReadyForReview,
            422,
            'You can only approve a customization that is ready for review.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::Completed,
        ]);

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }
}