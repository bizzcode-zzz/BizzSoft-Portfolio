<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductLicense;
use App\Models\ProductLicenseActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LicenseValidationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => [
                'required',
                'string',
                'max:100',
            ],
            'domain' => [
                'required',
                'string',
                'max:253',
            ],
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));
        $domain = strtolower(trim($validated['domain']));

        return DB::transaction(function () use (
            $licenseKey,
            $domain,
            $request
        ): JsonResponse {
            $license = ProductLicense::query()
                ->where('license_key', $licenseKey)
                ->lockForUpdate()
                ->first();

            if (! $license) {
                ProductLicenseActivity::create([
                    'product_license_id' => null,
                    'event' => 'invalid_license',
                    'attempted_domain' => $domain,
                    'license_key_fingerprint' => hash(
                        'sha256',
                        $licenseKey
                    ),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'http_status' => 404,
                ]);

                return response()->json([
                    'valid' => false,
                    'status' => 'invalid_license',
                ], 404);
            }

            if ($license->status === 'revoked') {
                ProductLicenseActivity::create([
                    'product_license_id' => $license->id,
                    'event' => 'revoked_attempt',
                    'attempted_domain' => $domain,
                    'license_key_fingerprint' => hash(
                        'sha256',
                        $licenseKey
                    ),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'http_status' => 403,
                ]);

                return response()->json([
                    'valid' => false,
                    'status' => 'revoked',
                    'domain' => $license->production_domain,
                ], 403);
            }

            if (
                $license->status === 'unactivated' ||
                $license->production_domain === null
            ) {
                return response()->json([
                    'valid' => false,
                    'status' => 'unactivated',
                ], 409);
            }

            if ($license->production_domain !== $domain) {
                ProductLicenseActivity::create([
                    'product_license_id' => $license->id,
                    'event' => 'domain_mismatch',
                    'attempted_domain' => $domain,
                    'license_key_fingerprint' => hash(
                        'sha256',
                        $licenseKey
                    ),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'http_status' => 409,
                ]);

                return response()->json([
                    'valid' => false,
                    'status' => 'domain_mismatch',
                    'domain' => $license->production_domain,
                ], 409);
            }

            $license->forceFill([
                'last_validated_at' => now(),
            ])->save();

            ProductLicenseActivity::create([
                'product_license_id' => $license->id,
                'event' => 'validation_success',
                'attempted_domain' => $domain,
                'license_key_fingerprint' => hash(
                    'sha256',
                    $licenseKey
                ),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'http_status' => 200,
            ]);

            return response()->json([
                'valid' => true,
                'status' => 'active',
                'domain' => $license->production_domain,
            ]);
        });
    }
}
