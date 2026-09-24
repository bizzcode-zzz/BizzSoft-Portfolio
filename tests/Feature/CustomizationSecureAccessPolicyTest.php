<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomizationSecureAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_view_any_secure_access_record(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $secureAccess = CustomizationSecureAccess::factory()->create();

        $this->assertTrue(
            $admin->can('view', $secureAccess)
        );
    }

    public function test_customer_can_view_secure_access_for_own_customization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->assertTrue(
            $customer->can('view', $secureAccess)
        );
    }

    public function test_customer_cannot_view_secure_access_for_another_customers_customization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->assertFalse(
            $customer->can('view', $secureAccess)
        );
    }

    public function test_customer_can_submit_customer_to_admin_access_for_own_customization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertTrue(
            $customer->can('submit', $secureAccess)
        );
    }

    public function test_customer_cannot_submit_customer_to_admin_access_for_another_customer(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertFalse(
            $customer->can('submit', $secureAccess)
        );
    }

    public function test_customer_cannot_submit_admin_to_customer_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertFalse(
            $customer->can('submit', $secureAccess)
        );
    }

    public function test_admin_can_submit_admin_to_customer_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertTrue(
            $admin->can('submit', $secureAccess)
        );
    }

    public function test_admin_can_reveal_customer_to_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertTrue(
            $admin->can('reveal', $secureAccess)
        );
    }

    public function test_customer_can_reveal_admin_to_customer_access_for_own_customization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertTrue(
            $customer->can('reveal', $secureAccess)
        );
    }

    public function test_customer_cannot_reveal_customer_to_admin_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertFalse(
            $customer->can('reveal', $secureAccess)
        );
    }

    public function test_customer_cannot_reveal_admin_to_customer_access_for_another_customer(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertFalse(
            $customer->can('reveal', $secureAccess)
        );
    }

    public function test_only_admin_can_close_secure_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->assertTrue(
            $admin->can('close', $secureAccess)
        );

        $this->assertFalse(
            $customer->can('close', $secureAccess)
        );
    }
}