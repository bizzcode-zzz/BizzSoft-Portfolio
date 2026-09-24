<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationSecureAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_request_secure_access_from_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Production Hosting Access',
                ]
            );

        $response->assertRedirect();

        $secureAccess = $customizationRequest
            ->secureAccesses()
            ->firstOrFail();

        $this->assertSame(
            $admin->id,
            $secureAccess->created_by
        );

        $this->assertSame(
            CustomizationSecureAccessDirection::CustomerToAdmin,
            $secureAccess->direction
        );

        $this->assertSame(
            CustomizationSecureAccessType::Hosting,
            $secureAccess->type
        );

        $this->assertSame(
            'Production Hosting Access',
            $secureAccess->label
        );

        $this->assertSame(
            CustomizationSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->login_url);
        $this->assertNull($secureAccess->username);
        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->notes);
        $this->assertNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_secure_access_type_must_be_valid(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from("/admin/customizations/{$customizationRequest->id}")
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access",
                [
                    'type' => 'invalid-type',
                    'label' => 'Production Access',
                ]
            );

        $response
            ->assertRedirect(
                "/admin/customizations/{$customizationRequest->id}"
            )
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_secure_access_label_is_required(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from("/admin/customizations/{$customizationRequest->id}")
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => '',
                ]
            );

        $response
            ->assertRedirect(
                "/admin/customizations/{$customizationRequest->id}"
            )
            ->assertSessionHasErrors('label');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_customer_cannot_request_secure_access_through_admin_route(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Production Hosting Access',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_guest_cannot_request_secure_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this->post(
            "/admin/customizations/{$customizationRequest->id}/secure-access",
            [
                'type' => CustomizationSecureAccessType::Hosting->value,
                'label' => 'Production Hosting Access',
            ]
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }
}