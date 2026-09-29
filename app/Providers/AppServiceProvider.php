<?php

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Providers\Paddle\PaddleGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGateway::class,
            PaddleGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('licenses', function (Request $request) {
            $licenseKey = strtoupper(
                trim((string) $request->input('license_key'))
            );

            $licenseFingerprint = hash(
                'sha256',
                $licenseKey
            );

            $rateLimitedResponse = static function (
                Request $request,
                array $headers
            ) {
                return response()->json([
                    'valid' => false,
                    'status' => 'rate_limited',
                ], 429, $headers);
            };

            return [
                Limit::perMinute(60)
                    ->by('license-ip:'.$request->ip())
                    ->response($rateLimitedResponse),

                Limit::perMinute(10)
                    ->by(
                        'license-key:'
                        .$request->ip()
                        .':'
                        .$licenseFingerprint
                    )
                    ->response($rateLimitedResponse),
            ];
        });
    }
}