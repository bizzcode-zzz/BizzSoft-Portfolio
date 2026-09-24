<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $customizationRequest->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return back();
    }
}