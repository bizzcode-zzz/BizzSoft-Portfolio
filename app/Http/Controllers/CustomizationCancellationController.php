<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;

class CustomizationCancellationController extends Controller
{
    public function cancel(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $allowedStatuses = [
            CustomizationRequestStatus::Submitted,
            CustomizationRequestStatus::UnderReview,
            CustomizationRequestStatus::NeedsInformation,
            CustomizationRequestStatus::QuoteSent,
            CustomizationRequestStatus::Accepted,
        ];

        abort_unless(
            in_array($customizationRequest->status, $allowedStatuses, true),
            422,
            'This customization request can no longer be cancelled.'
        );

        $customizationRequest->update([
            'status' => CustomizationRequestStatus::Cancelled,
        ]);

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }
}
