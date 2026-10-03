<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SensitiveRouteRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_uses_registration_limiter(): void
    {
        $route = $this->routeByMethodAndUri('POST', 'register');

        $this->assertContains(
            'throttle:registration',
            $route->gatherMiddleware()
        );
    }

    public function test_sensitive_named_routes_have_expected_limiters(): void
    {
        $expected = [
            'customer.orders.store' => 'checkout',
            'customer.orders.payment.checkout' => 'checkout',
            'customizations.payment.checkout' => 'checkout',

            'customer.products.releases.download' => 'downloads',

            'tickets.store' => 'support-writes',
            'tickets.replies.store' => 'support-writes',
            'customizations.store' => 'support-writes',
            'customizations.cancel' => 'support-writes',
            'customizations.replies.store' => 'support-writes',
            'customizations.messages.store' => 'support-writes',
            'customizations.quote.accept' => 'support-writes',
            'customizations.quote.decline' => 'support-writes',
            'customizations.request-revision' => 'support-writes',
            'customizations.approve' => 'support-writes',

            'admin.tickets.replies.store' => 'support-writes',
            'admin.tickets.resolve' => 'support-writes',
            'admin.tickets.close' => 'support-writes',
            'admin.customizations.start-review' => 'support-writes',
            'admin.customizations.decline' => 'support-writes',
            'admin.customizations.request-information' => 'support-writes',
            'admin.customizations.messages.store' => 'support-writes',
            'admin.customizations.quote.store' => 'support-writes',
            'admin.customizations.start-development' => 'support-writes',
            'admin.customizations.ready-for-review' => 'support-writes',
            'admin.customizations.resume-development' => 'support-writes',

            'tickets.secure-access.submit' => 'secure-access',
            'tickets.secure-access.reveal' => 'secure-access',
            'customizations.secure-access.submit' => 'secure-access',
            'customizations.secure-access.reveal' => 'secure-access',
            'admin.tickets.secure-access.store' => 'secure-access',
            'admin.tickets.secure-access.handoff' => 'secure-access',
            'admin.tickets.secure-access.reveal' => 'secure-access',
            'admin.tickets.secure-access.close' => 'secure-access',
            'admin.customizations.secure-access.store' => 'secure-access',
            'admin.customizations.secure-access.handoff' => 'secure-access',
            'admin.customizations.secure-access.reveal' => 'secure-access',
            'admin.customizations.secure-access.close' => 'secure-access',
        ];

        foreach ($expected as $routeName => $limiter) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Expected route [{$routeName}] to exist."
            );

            $this->assertContains(
                "throttle:{$limiter}",
                $route->gatherMiddleware(),
                "Route [{$routeName}] is missing limiter [{$limiter}]."
            );
        }
    }

    public function test_registration_limiter_returns_429_after_five_requests(): void
    {
        $uri = $this->probeRoute('registration', 'registration');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson($uri)->assertOk();
        }

        $this->postJson($uri)->assertStatus(429);
    }

    public function test_checkout_limiter_returns_429_after_ten_requests_per_user(): void
    {
        $user = User::factory()->create();
        $uri = $this->probeRoute('checkout', 'checkout');

        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson($uri)->assertOk();
        }

        $this->postJson($uri)->assertStatus(429);
    }

    public function test_download_limiter_returns_429_after_thirty_requests_per_user(): void
    {
        $user = User::factory()->create();
        $uri = $this->probeRoute('downloads', 'downloads');

        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->postJson($uri)->assertOk();
        }

        $this->postJson($uri)->assertStatus(429);
    }

    public function test_support_write_limiter_returns_429_after_twenty_requests_per_user(): void
    {
        $user = User::factory()->create();
        $uri = $this->probeRoute('support-writes', 'support-writes');

        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson($uri)->assertOk();
        }

        $this->postJson($uri)->assertStatus(429);
    }

    public function test_secure_access_limiter_returns_429_after_ten_requests_per_user(): void
    {
        $user = User::factory()->create();
        $uri = $this->probeRoute('secure-access', 'secure-access');

        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson($uri)->assertOk();
        }

        $this->postJson($uri)->assertStatus(429);
    }

    private function probeRoute(string $slug, string $limiter): string
    {
        $uri = "/__rate-limit-probe/{$slug}";

        Route::post(
            $uri,
            static fn () => response()->json(['ok' => true])
        )->middleware("throttle:{$limiter}");

        return $uri;
    }

    private function routeByMethodAndUri(
        string $method,
        string $uri
    ): RoutingRoute {
        foreach (Route::getRoutes() as $route) {
            if (
                in_array($method, $route->methods(), true)
                && $route->uri() === $uri
            ) {
                return $route;
            }
        }

        $this->fail(
            "Unable to find [{$method}] route for URI [{$uri}]."
        );
    }
}
