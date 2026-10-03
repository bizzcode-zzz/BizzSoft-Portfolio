<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class LoginRateLimitingTest extends TestCase
{
    public function test_sixth_attempt_is_blocked_before_authentication_and_does_not_flash_password(): void
    {
        Auth::shouldReceive('attempt')->times(5)->andReturn(false);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login()->assertSessionHasErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $this->login()->assertRedirect('/login')->assertSessionHasErrors([
            'email' => __('auth.throttle', ['seconds' => 60, 'minutes' => 1]),
        ])->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_email_case_and_whitespace_do_not_bypass_the_limit(): void
    {
        Auth::shouldReceive('attempt')->times(5)->andReturn(false);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login();
        }

        $this->login(' CUSTOMER@EXAMPLE.COM ')->assertSessionHasErrors([
            'email' => __('auth.throttle', ['seconds' => 60, 'minutes' => 1]),
        ]);
    }

    public function test_account_limit_does_not_block_other_accounts_or_other_ips(): void
    {
        Auth::shouldReceive('attempt')->times(7)->andReturn(false);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login();
        }

        $this->login('another@example.com')->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->login()->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function test_ip_limit_blocks_attempts_across_different_accounts(): void
    {
        Auth::shouldReceive('attempt')->times(30)->andReturn(false);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->login("customer{$attempt}@example.com");
        }

        $this->login('next@example.com')->assertSessionHasErrors([
            'email' => __('auth.throttle', ['seconds' => 60, 'minutes' => 1]),
        ]);
    }

    public function test_attempts_are_allowed_again_after_the_window_expires(): void
    {
        Auth::shouldReceive('attempt')->times(6)->andReturn(false);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->login();
        }

        $this->travel(61)->seconds();

        $this->login()->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function test_customer_can_still_log_in_after_four_failed_attempts(): void
    {
        $this->assertSuccessfulLogin(false, 'dashboard');
    }

    public function test_admin_can_still_log_in_after_four_failed_attempts(): void
    {
        $this->assertSuccessfulLogin(true, 'admin.dashboard');
    }

    private function assertSuccessfulLogin(bool $admin, string $route): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 123;
        $user->shouldReceive('hasRole')->with('admin')->andReturn($admin);
        Auth::shouldReceive('attempt')->times(4)->andReturn(false)->ordered();
        Auth::shouldReceive('attempt')->once()->andReturnUsing(function () use ($user) {
            Auth::guard()->setUser($user);

            return true;
        })->ordered();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->login();
        }

        $this->login()->assertRedirect(route($route));
        $this->assertAuthenticatedAs($user);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
        Auth::swap(Mockery::mock(AuthManager::class, [$this->app])->makePartial());
    }

    private function login(string $email = 'customer@example.com'): TestResponse
    {
        return $this->from('/login')->post('/login', [
            'email' => $email,
            'password' => 'incorrect-password',
        ]);
    }
}
