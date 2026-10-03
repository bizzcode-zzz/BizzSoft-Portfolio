<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLicense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function index(): Response
    {
        $licenses = ProductLicense::query()
            ->with([
                'order:id,order_number',
                'ownership.user:id,name,email',
                'ownership.product:id,name,slug',
                'revoker:id,name,email',
                'activities' => fn ($query) => $query
                    ->latest('id')
                    ->limit(5),
            ])
            ->latest('id')
            ->paginate(25)
            ->through(fn (ProductLicense $license) => [
                'id' => $license->id,

                'license_key' => $license->license_key,

                'status' => $license->status,
                'production_domain' => $license->production_domain,
                'activated_at' => $license->activated_at,
                'last_validated_at' => $license->last_validated_at,
                'revoked_at' => $license->revoked_at,
                'revocation_reason' => $license->revocation_reason,
                'revoker' => $license->revoker ? [
                    'id' => $license->revoker->id,
                    'name' => $license->revoker->name,
                    'email' => $license->revoker->email,
                ] : null,

                'order' => [
                    'id' => $license->order->id,
                    'order_number' => $license->order->order_number,
                ],

                'customer' => [
                    'id' => $license->ownership->user->id,
                    'name' => $license->ownership->user->name,
                    'email' => $license->ownership->user->email,
                ],

                'activities' => $license->activities
                    ->map(fn ($activity) => [
                        'id' => $activity->id,
                        'event' => $activity->event,
                        'attempted_domain' => $activity->attempted_domain,
                        'ip_address' => $activity->ip_address,
                        'http_status' => $activity->http_status,
                        'created_at' => $activity->created_at,
                    ])
                    ->values()
                    ->all(),

                'product' => [
                    'id' => $license->ownership->product->id,
                    'name' => $license->ownership->product->name,
                    'slug' => $license->ownership->product->slug,
                ],
            ]);

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses,
        ]);
    }

    public function revoke(
        Request $request,
        ProductLicense $productLicense
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        if ($productLicense->status === 'revoked') {
            throw ValidationException::withMessages([
                'reason' => 'This license has already been revoked.',
            ]);
        }

        $productLicense->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'revocation_reason' => $validated['reason'],
        ]);

        return redirect()
            ->route('admin.licenses.index')
            ->with('success', 'License revoked successfully.');
    }
}
