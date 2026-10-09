<?php

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Providers\Paddle\PaddleGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

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
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email');
            $fingerprint = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');

            $response = static function (Request $request, array $headers) {
                throw ValidationException::withMessages([
                    'email' => __('auth.throttle', [
                        'seconds' => $headers['Retry-After'],
                        'minutes' => (int) ceil($headers['Retry-After'] / 60),
                    ]),
                ]);
            };

            return [
                Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($response),
                Limit::perMinute(5)->by('login-account:'.$request->ip().':'.$fingerprint)->response($response),
            ];
        });
        RateLimiter::for('registration', function (Request $request) {
            return [
                Limit::perMinute(5)
                    ->by('registration-minute-ip:'.$request->ip()),

                Limit::perHour(20)
                    ->by('registration-hour-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('contact', function (Request $request) {
            return [
                Limit::perMinute(3)
                    ->by('contact-minute-ip:'.$request->ip()),

                Limit::perHour(10)
                    ->by('contact-hour-ip:'.$request->ip()),
            ];
        });
        RateLimiter::for('checkout', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();
            $userKey = $userId !== null
                ? (string) $userId
                : 'guest:'.$request->ip();

            return [
                Limit::perMinute(10)
                    ->by('checkout-user:'.$userKey),

                Limit::perMinute(30)
                    ->by('checkout-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('downloads', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();
            $userKey = $userId !== null
                ? (string) $userId
                : 'guest:'.$request->ip();

            return [
                Limit::perMinute(30)
                    ->by('downloads-user:'.$userKey),

                Limit::perMinute(60)
                    ->by('downloads-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('support-writes', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();
            $userKey = $userId !== null
                ? (string) $userId
                : 'guest:'.$request->ip();

            return [
                Limit::perMinute(20)
                    ->by('support-write-user:'.$userKey),

                Limit::perMinute(60)
                    ->by('support-write-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('secure-access', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier();
            $userKey = $userId !== null
                ? (string) $userId
                : 'guest:'.$request->ip();

            return [
                Limit::perMinute(10)
                    ->by('secure-access-user:'.$userKey),

                Limit::perMinute(30)
                    ->by('secure-access-ip:'.$request->ip()),
            ];
        });

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
