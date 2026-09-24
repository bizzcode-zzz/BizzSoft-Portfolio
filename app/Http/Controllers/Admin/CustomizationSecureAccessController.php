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

class CustomizationSecureAccessController extends Controller
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
        ]);

        $customizationRequest->secureAccesses()->create([
            'created_by' => $request->user()->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'type' => $validated['type'],
            'label' => $validated['label'],
            'status' => CustomizationSecureAccessStatus::Requested,

            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,

            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        return back();
    }
}