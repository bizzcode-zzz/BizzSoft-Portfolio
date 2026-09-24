<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationSecureAccessRevealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_customer_can_reveal_admin_handoff_for_own_customization(): void
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
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
            'login_url' => 'https://hosting.example.com/login',
            'username' => 'customer-user',
            'secret' => 'temporary-password-123',
            'notes' => 'Please change the password after signing in.',
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'secure_access.id',
                $secureAccess->id
            )
            ->assertJsonPath(
                'secure_access.type',
                $secureAccess->type->value
            )
            ->assertJsonPath(
                'secure_access.label',
                $secureAccess->label
            )
            ->assertJsonPath(
                'secure_access.login_url',
                'https://hosting.example.com/login'
            )
            ->assertJsonPath(
                'secure_access.username',
                'customer-user'
            )
            ->assertJsonPath(
                'secure_access.secret',
                'temporary-password-123'
            )
            ->assertJsonPath(
                'secure_access.notes',
                'Please change the password after signing in.'
            );

        $secureAccess->refresh();

        $this->assertNotNull($secureAccess->viewed_at);
    }

    public function test_first_viewed_at_is_preserved_on_later_customer_reveals(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $firstViewedAt = now()->subHour()->startOfSecond();

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
            'viewed_at' => $firstViewedAt,
            'closed_at' => null,
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertOk();

        $secureAccess->refresh();

        $this->assertTrue(
            $secureAccess->viewed_at->equalTo($firstViewedAt)
        );
    }

    public function test_customer_cannot_reveal_customer_to_admin_credentials(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $customer->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertForbidden();
    }

    public function test_customer_cannot_reveal_admin_handoff_for_another_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($otherCustomer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertForbidden();
    }

    public function test_customer_cannot_reveal_requested_secure_access(): void
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
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Requested,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertStatus(422);
    }

    public function test_customer_cannot_reveal_closed_secure_access(): void
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
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Closed,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'closed_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertStatus(422);
    }

    public function test_admin_cannot_use_customer_reveal_route(): void
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
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($admin)
            ->getJson(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertForbidden();
    }

    public function test_guest_cannot_reveal_customer_secure_access(): void
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
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->get(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertRedirect('/login');
    }

    public function test_scoped_binding_rejects_secure_access_from_different_customization(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $firstRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secondRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $secondRequest->id,
            'created_by' => $admin->id,
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                "/customizations/{$firstRequest->id}/secure-access/{$secureAccess->id}/reveal"
            )
            ->assertNotFound();
    }
}