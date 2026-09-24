<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationRequestStatusController extends Controller
{
    public function startReview(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::Submitted,
            422,
            'Only submitted customization requests can be moved under review.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }

    public function decline(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $allowedStatuses = [
            CustomizationRequestStatus::UnderReview,
            CustomizationRequestStatus::NeedsInformation,
        ];

        abort_unless(
            in_array(
                $customizationRequest->status,
                $allowedStatuses,
                true
            ),
            422,
            'Only customization requests under review or waiting for customer information can be declined.'
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use (
            $request,
            $customizationRequest,
            $validated
        ) {
            $customizationRequest->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $customizationRequest->update([
                'status' => CustomizationRequestStatus::RequestDeclined,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }
}