<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_permission_protected_route(): void
    {
        $response = $this->get('/admin/projects/create-check');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_permission_protected_route(): void
    {
        $customer = User::factory()->create();

        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/admin/projects/create-check');

        $response->assertForbidden();
    }

    public function test_admin_with_create_projects_permission_can_access_route(): void
    {
        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/admin/projects/create-check');

        $response
            ->assertOk()
            ->assertJson([
                'allowed' => true,
            ]);
    }

    public function test_admin_without_create_projects_permission_cannot_access_route(): void
    {
        $adminRole = Role::findByName('admin');

        $adminRole->revokePermissionTo('create projects');

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/admin/projects/create-check');

        $response->assertForbidden();
    }
}