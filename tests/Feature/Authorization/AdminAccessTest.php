<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create();

        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_admin_cannot_access_customer_dashboard(): void
    {
        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_customer_can_access_customer_dashboard(): void
    {
        $customer = User::factory()->create();

        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'password' => 'password',
        ]);

        $admin->assignRole('admin');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_login_redirects_to_customer_dashboard(): void
    {
        $customer = User::factory()->create([
            'password' => 'password',
        ]);

        $customer->assignRole('customer');

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($customer);

        $response->assertRedirect(route('dashboard'));
    }
}