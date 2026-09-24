<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationSecureAccessSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_customer_can_submit_requested_secure_access_for_own_customization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Requested,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => null,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'login_url' => 'https://hosting.example.com/login',
                    'username' => 'customer-hosting-user',
                    'secret' => 'temporary-secret-password',
                    'notes' => 'Temporary production hosting access.',
                ]
            );

        $response->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            CustomizationSecureAccessStatus::Submitted,
            $secureAccess->status
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
            'temporary-secret-password',
            $secureAccess->secret
        );

        $this->assertSame(
            'Temporary production hosting access.',
            $secureAccess->notes
        );

        $this->assertNotNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_submitted_sensitive_values_are_encrypted_in_raw_database(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Requested,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => null,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'login_url' => 'https://private.example.com/login',
                    'username' => 'private-user',
                    'secret' => 'raw-database-must-not-see-this',
                    'notes' => 'Sensitive deployment notes.',
                ]
            )
            ->assertRedirect();

        $stored = DB::table('customization_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://private.example.com/login',
            $stored->login_url
        );

        $this->assertNotSame(
            'private-user',
            $stored->username
        );

        $this->assertNotSame(
            'raw-database-must-not-see-this',
            $stored->secret
        );

        $this->assertNotSame(
            'Sensitive deployment notes.',
            $stored->notes
        );
    }

    public function test_customer_cannot_submit_secure_access_for_another_customer(): void
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
            'status' => CustomizationSecureAccessStatus::Requested,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'secret' => 'should-not-be-saved',
                ]
            );

        $response->assertForbidden();
    }

    public function test_admin_cannot_submit_through_customer_secure_access_route(): void
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
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Requested,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'secret' => 'should-not-be-saved',
                ]
            );

        $response->assertForbidden();
    }

    public function test_customer_cannot_resubmit_already_submitted_secure_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Submitted,
            'secret' => 'original-secret',
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'secret' => 'replacement-secret',
                ]
            );

        $response->assertStatus(422);

        $secureAccess->refresh();

        $this->assertSame('original-secret', $secureAccess->secret);

        $this->assertSame(
            CustomizationSecureAccessStatus::Submitted,
            $secureAccess->status
        );
    }

    public function test_customer_cannot_submit_closed_secure_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Closed,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'secret' => 'new-secret',
                ]
            );

        $response->assertStatus(422);

        $secureAccess->refresh();

        $this->assertNull($secureAccess->secret);

        $this->assertSame(
            CustomizationSecureAccessStatus::Closed,
            $secureAccess->status
        );
    }

    public function test_secret_is_required_when_submitting_secure_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Requested,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($customer)
            ->from("/customizations/{$customizationRequest->id}")
            ->post(
                "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
                [
                    'login_url' => 'https://hosting.example.com',
                    'username' => 'customer-user',
                    'secret' => '',
                ]
            );

        $response
            ->assertRedirect("/customizations/{$customizationRequest->id}")
            ->assertSessionHasErrors('secret');

        $secureAccess->refresh();

        $this->assertSame(
            CustomizationSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->submitted_at);
    }

    public function test_guest_cannot_submit_secure_access(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Requested,
        ]);

        $response = $this->post(
            "/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/submit",
            [
                'secret' => 'guest-secret',
            ]
        );

        $response->assertRedirect('/login');
    }
}
