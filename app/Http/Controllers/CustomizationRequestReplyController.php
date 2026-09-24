<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationRequestReplyController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::NeedsInformation,
            422,
            'You can only send information when this customization request needs information.'
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
                'status' => CustomizationRequestStatus::UnderReview,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }
}