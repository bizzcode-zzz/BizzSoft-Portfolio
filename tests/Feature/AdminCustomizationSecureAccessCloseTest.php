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

class AdminCustomizationSecureAccessCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_close_submitted_secure_access_and_purge_sensitive_values(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $submittedAt = now()->subHours(2)->startOfSecond();
        $viewedAt = now()->subHour()->startOfSecond();

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $customer->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Submitted,
            'login_url' => 'https://hosting.example.com/login',
            'username' => 'temporary-user',
            'secret' => 'temporary-password',
            'notes' => 'Temporary production credentials.',
            'submitted_at' => $submittedAt,
            'viewed_at' => $viewedAt,
            'closed_at' => null,
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/close"
            )
            ->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            CustomizationSecureAccessStatus::Closed,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->login_url);
        $this->assertNull($secureAccess->username);
        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->notes);

        $this->assertTrue(
            $secureAccess->submitted_at->equalTo($submittedAt)
        );

        $this->assertTrue(
            $secureAccess->viewed_at->equalTo($viewedAt)
        );

        $this->assertNotNull($secureAccess->closed_at);
    }

    public function test_admin_can_close_requested_secure_access(): void
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

        $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/close"
            )
            ->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            CustomizationSecureAccessStatus::Closed,
            $secureAccess->status
        );

        $this->assertNotNull($secureAccess->closed_at);
    }

    public function test_closed_secure_access_cannot_be_closed_again(): void
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
            'created_by' => $customer->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Closed,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'closed_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/close"
            )
            ->assertStatus(422);
    }

    public function test_customer_cannot_close_secure_access(): void
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
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/close"
            )
            ->assertForbidden();
    }

    public function test_guest_cannot_close_secure_access(): void
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
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/secure-access/{$secureAccess->id}/close"
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
            'created_by' => $customer->id,
            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$firstRequest->id}/secure-access/{$secureAccess->id}/close"
            )
            ->assertNotFound();
    }
}