<?php

namespace Tests\Feature;

use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomizationRequestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_customer_can_view_customization_request_list(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->assertTrue(
            $customer->can('viewAny', CustomizationRequest::class)
        );
    }

    public function test_admin_can_view_customization_request_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue(
            $admin->can('viewAny', CustomizationRequest::class)
        );
    }

    public function test_customer_can_view_own_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->assertTrue(
            $customer->can('view', $request)
        );
    }

    public function test_customer_cannot_view_another_customers_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $this->assertFalse(
            $customer->can('view', $request)
        );
    }

    public function test_admin_can_view_any_customization_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->assertTrue(
            $admin->can('view', $request)
        );
    }

    public function test_customer_can_create_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->assertTrue(
            $customer->can('create', CustomizationRequest::class)
        );
    }

    public function test_admin_cannot_use_customer_creation_flow(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertFalse(
            $admin->can('create', CustomizationRequest::class)
        );
    }

    public function test_customer_can_update_own_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->assertTrue(
            $customer->can('update', $request)
        );
    }

    public function test_customer_cannot_update_another_customers_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $this->assertFalse(
            $customer->can('update', $request)
        );
    }

    public function test_admin_can_update_any_customization_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->assertTrue(
            $admin->can('update', $request)
        );
    }

    public function test_customer_cannot_delete_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $request = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this->assertFalse(
            $customer->can('delete', $request)
        );
    }

    public function test_admin_can_delete_customization_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $request = CustomizationRequest::factory()->create();

        $this->assertTrue(
            $admin->can('delete', $request)
        );
    }

    public function test_restore_is_not_allowed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $request = CustomizationRequest::factory()->create();

        $this->assertFalse(
            $admin->can('restore', $request)
        );
    }

    public function test_force_delete_is_not_allowed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $request = CustomizationRequest::factory()->create();

        $this->assertFalse(
            $admin->can('forceDelete', $request)
        );
    }
}