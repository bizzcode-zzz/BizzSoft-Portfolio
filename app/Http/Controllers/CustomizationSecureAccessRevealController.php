<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomizationSecureAccessRevealController extends Controller
{
    public function show(
        Request $request,
        CustomizationRequest $customizationRequest,
        CustomizationSecureAccess $secureAccess
    ): JsonResponse {
        $this->authorize('reveal', $secureAccess);

        if (
            $secureAccess->direction !==
            CustomizationSecureAccessDirection::AdminToCustomer
        ) {
            abort(
                422,
                'This secure access record cannot be revealed by the customer.'
            );
        }

        if (
            $secureAccess->status !==
            CustomizationSecureAccessStatus::Submitted
        ) {
            abort(
                422,
                'These secure credentials are not available for reveal.'
            );
        }

        if ($secureAccess->viewed_at === null) {
            $secureAccess->update([
                'viewed_at' => now(),
            ]);
        }

        return response()->json([
            'secure_access' => [
                'id' => $secureAccess->id,
                'type' => $secureAccess->type->value,
                'label' => $secureAccess->label,

                'login_url' => $secureAccess->login_url,
                'username' => $secureAccess->username,
                'secret' => $secureAccess->secret,
                'notes' => $secureAccess->notes,

                'submitted_at' => $secureAccess->submitted_at?->toISOString(),
                'viewed_at' => $secureAccess->viewed_at?->toISOString(),
            ],
        ]);
    }
}