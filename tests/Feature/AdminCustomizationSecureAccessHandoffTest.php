<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationSecureAccessHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_create_secure_access_handoff_for_customer(): void
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
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'New Hosting Account',
                    'login_url' => 'https://hosting.example.com/login',
                    'username' => 'customer-hosting-user',
                    'secret' => 'temporary-password-123',
                    'notes' => 'Please change the password after signing in.',
                ]
            );

        $response->assertRedirect();

        $secureAccess = CustomizationSecureAccess::query()->firstOrFail();

        $this->assertSame(
            $customizationRequest->id,
            $secureAccess->customization_request_id
        );

        $this->assertSame(
            $admin->id,
            $secureAccess->created_by
        );

        $this->assertSame(
            CustomizationSecureAccessDirection::AdminToCustomer,
            $secureAccess->direction
        );

        $this->assertSame(
            CustomizationSecureAccessType::Hosting,
            $secureAccess->type
        );

        $this->assertSame(
            CustomizationSecureAccessStatus::Submitted,
            $secureAccess->status
        );

        $this->assertSame(
            'New Hosting Account',
            $secureAccess->label
        );

        $this->assertSame(
            'https://hosting.example.com/login',
            $secureAccess->login_url
        );

        $this->assertSame(
            'customer-hosting-user',
            $secureAccess->username
        );

        $this->assertSame(
            'temporary-password-123',
            $secureAccess->secret
        );

        $this->assertSame(
            'Please change the password after signing in.',
            $secureAccess->notes
        );

        $this->assertNotNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_handoff_sensitive_values_are_encrypted_in_raw_database(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Production Hosting',
                    'login_url' => 'https://secure.example.com/login',
                    'username' => 'production-user',
                    'secret' => 'super-secret-temporary-password',
                    'notes' => 'Temporary customer access.',
                ]
            )
            ->assertRedirect();

        $secureAccess = CustomizationSecureAccess::query()->firstOrFail();

        $rawRecord = DB::table('customization_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://secure.example.com/login',
            $rawRecord->login_url
        );

        $this->assertNotSame(
            'production-user',
            $rawRecord->username
        );

        $this->assertNotSame(
            'super-secret-temporary-password',
            $rawRecord->secret
        );

        $this->assertNotSame(
            'Temporary customer access.',
            $rawRecord->notes
        );

        $this->assertStringNotContainsString(
            'super-secret-temporary-password',
            $rawRecord->secret
        );
    }

    public function test_secret_is_required_for_secure_access_handoff(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Hosting Account',
                    'login_url' => 'https://hosting.example.com/login',
                    'username' => 'customer-user',
                    'secret' => '',
                ]
            )
            ->assertSessionHasErrors('secret');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_type_is_required_and_must_be_valid(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => 'invalid-type',
                    'label' => 'Temporary Access',
                    'secret' => 'temporary-secret',
                ]
            )
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_label_is_required_for_secure_access_handoff(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => '',
                    'secret' => 'temporary-secret',
                ]
            )
            ->assertSessionHasErrors('label');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_customer_cannot_create_admin_secure_access_handoff(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Hosting Account',
                    'secret' => 'temporary-secret',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }

    public function test_guest_cannot_create_secure_access_handoff(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->post(
                "/admin/customizations/{$customizationRequest->id}/secure-access/handoff",
                [
                    'type' => CustomizationSecureAccessType::Hosting->value,
                    'label' => 'Hosting Account',
                    'secret' => 'temporary-secret',
                ]
            )
            ->assertRedirect('/login');

        $this->assertDatabaseCount(
            'customization_secure_accesses',
            0
        );
    }
}