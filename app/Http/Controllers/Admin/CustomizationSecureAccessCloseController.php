<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationSecureAccessStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationSecureAccessCloseController extends Controller
{
    public function close(
        Request $request,
        CustomizationRequest $customizationRequest,
        CustomizationSecureAccess $secureAccess
    ): RedirectResponse {
        $this->authorize('close', $secureAccess);

        if ($secureAccess->status === CustomizationSecureAccessStatus::Closed) {
            abort(
                422,
                'This secure access record is already closed.'
            );
        }

        DB::transaction(function () use ($secureAccess) {
            $secureAccess->update([
                // Permanently purge sensitive values.
                'login_url' => null,
                'username' => null,
                'secret' => null,
                'notes' => null,

                // Preserve non-sensitive audit history.
                'status' => CustomizationSecureAccessStatus::Closed,
                'closed_at' => now(),
            ]);
        });

        return back();
    }
}