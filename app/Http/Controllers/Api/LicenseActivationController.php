<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Licensing\ProductionDomain;
use App\Models\Order;
use App\Models\ProductLicense;
use App\Models\ProductLicenseActivity;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseActivationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => [
                'required',
                'string',
                'max:100',
            ],
            'product_key' => [
                'required',
                'string',
                'uuid',
            ],
            'domain' => [
                'required',
                'string',
                'max:253',
            ],
        ]);

        $licenseKey = strtoupper(trim($validated['license_key']));
        $productKey = strtolower(trim($validated['product_key']));
        $domain = ProductionDomain::normalize($validated['domain']);

        if ($domain === null) {
            throw ValidationException::withMessages([
                'domain' => 'The domain must be a valid production hostname.',
            ]);
        }

        return DB::transaction(function () use (
            $licenseKey,
            $productKey,
            $domain,
            $request
        ): JsonResponse {
            $hint = ProductLicense::query()->where('license_key', $licenseKey)->first();
            if ($hint) {
                $order = Order::query()->whereKey($hint->order_id)->lockForUpdate()->first();
                if (! $order) {
                    return response()->json(['valid' => false, 'status' => 'invalid_purchase'], 403);
                }
                $order->payments()->orderBy('id')->lockForUpdate()->get();

                $product = $order->product()->first();

                if (! $product) {
                    return response()->json([
                        'valid' => false,
                        'status' => 'invalid_purchase',
                    ], 403);
                }

                if ($product->license_product_key !== $productKey) {
                    ProductLicenseActivity::create([
                        'product_license_id' => $hint->id,
                        'event' => 'product_mismatch',
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
            }
            $license = ProductLicense::query()->where('license_key', $licenseKey)->lockForUpdate()->first();
            if ($license && (! $hint || $license->order_id !== $hint->order_id)) {
                return response()->json(['valid' => false, 'status' => 'purchase_changed'], 409);
            }

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

            if (app(PaymentEntitlements::class)->orderIsHeld($license->order_id)) {
                ProductLicenseActivity::create([
                    'product_license_id' => $license->id, 'event' => 'payment_hold_attempt',
                    'attempted_domain' => $domain,
                    'license_key_fingerprint' => hash('sha256', $licenseKey),
                    'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'http_status' => 403,
                ]);

                return response()->json(['valid' => false, 'status' => 'payment_hold'], 403);
            }

            if (
                $license->production_domain !== null &&
                $license->production_domain !== $domain
            ) {
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

            if ($license->production_domain === null) {
                $license->forceFill([
                    'status' => 'active',
                    'production_domain' => $domain,
                    'activated_at' => now(),
                    'last_validated_at' => now(),
                ])->save();

                ProductLicenseActivity::create([
                    'product_license_id' => $license->id,
                    'event' => 'activation_success',
                    'attempted_domain' => $domain,
                    'license_key_fingerprint' => hash(
                        'sha256',
                        $licenseKey
                    ),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'http_status' => 200,
                ]);
            } else {
                $license->forceFill([
                    'status' => 'active',
                    'last_validated_at' => now(),
                ])->save();
            }

            return response()->json([
                'valid' => true,
                'status' => 'active',
                'domain' => $license->production_domain,
            ]);
        });
    }
}
