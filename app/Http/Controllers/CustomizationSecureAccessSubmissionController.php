<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomizationSecureAccessSubmissionController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest,
        CustomizationSecureAccess $secureAccess
    ): RedirectResponse {
        $this->authorize('submit', $secureAccess);

        if (
            $secureAccess->direction !==
            CustomizationSecureAccessDirection::CustomerToAdmin
        ) {
            abort(
                422,
                'This secure access request cannot be submitted by the customer.'
            );
        }

        if (
            $secureAccess->status !==
            CustomizationSecureAccessStatus::Requested
        ) {
            abort(
                422,
                'This secure access request is no longer accepting credentials.'
            );
        }

        $validated = $request->validate([
            'login_url' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'username' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'secret' => [
                'required',
                'string',
                'max:10000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $secureAccess->update([
            'login_url' => $validated['login_url'] ?? null,
            'username' => $validated['username'] ?? null,
            'secret' => $validated['secret'],
            'notes' => $validated['notes'] ?? null,

            'status' => CustomizationSecureAccessStatus::Submitted,
            'submitted_at' => now(),

            'viewed_at' => null,
            'closed_at' => null,
        ]);

        return back();
    }
}