<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_has_expected_project_permissions(): void
    {
        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $this->assertTrue($admin->can('view projects'));
        $this->assertTrue($admin->can('create projects'));
        $this->assertTrue($admin->can('edit projects'));
        $this->assertTrue($admin->can('delete projects'));
        $this->assertTrue($admin->can('manage users'));
    }

    public function test_customer_does_not_have_admin_permissions(): void
    {
        $customer = User::factory()->create();

        $customer->assignRole('customer');

        $this->assertFalse($customer->can('view projects'));
        $this->assertFalse($customer->can('create projects'));
        $this->assertFalse($customer->can('edit projects'));
        $this->assertFalse($customer->can('delete projects'));
        $this->assertFalse($customer->can('manage users'));
    }

    public function test_admin_role_receives_permissions_through_role(): void
    {
        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $this->assertTrue($admin->hasRole('admin'));

        $this->assertTrue(
            $admin->hasPermissionTo('edit projects')
        );

        $this->assertFalse(
            $admin->hasDirectPermission('edit projects')
        );
    }

    public function test_customer_cannot_gain_admin_permissions_from_customer_role(): void
    {
        $customer = User::factory()->create();

        $customer->assignRole('customer');

        $this->assertTrue($customer->hasRole('customer'));

        $this->assertFalse(
            $customer->hasPermissionTo('manage users')
        );
    }
}