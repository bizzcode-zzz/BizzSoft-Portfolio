<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationRequestInformationController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::UnderReview,
            422,
            'Information can only be requested while the customization request is under review.'
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
                'status' => CustomizationRequestStatus::NeedsInformation,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }
}