<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomizationSecureAccessHandoffController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::enum(CustomizationSecureAccessType::class),
            ],
            'label' => [
                'required',
                'string',
                'max:255',
            ],
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

        $customizationRequest->secureAccesses()->create([
            'created_by' => $request->user()->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'type' => $validated['type'],
            'label' => $validated['label'],

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