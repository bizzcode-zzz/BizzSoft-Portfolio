<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationConversationController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_if(
            in_array(
                $customizationRequest->status,
                [
                    CustomizationRequestStatus::Completed,
                    CustomizationRequestStatus::RequestDeclined,
                    CustomizationRequestStatus::Cancelled,
                    CustomizationRequestStatus::QuoteDeclined,
                ],
                true
            ),
            422,
            'Messages cannot be sent after this customization request is closed.'
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

            abort_if(
                in_array(
                    $lockedRequest->status,
                    [
                        CustomizationRequestStatus::Completed,
                        CustomizationRequestStatus::RequestDeclined,
                        CustomizationRequestStatus::Cancelled,
                        CustomizationRequestStatus::QuoteDeclined,
                    ],
                    true
                ),
                422,
                'Messages cannot be sent after this customization request is closed.'
            );

            $lockedRequest->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);
        });

        return back();
    }
}
