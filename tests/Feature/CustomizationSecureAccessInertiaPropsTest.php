<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomizationSecureAccessInertiaPropsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_customer_show_page_receives_secure_access_metadata_without_sensitive_values(): void
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
            'login_url' => 'https://secure.example.com/login',
            'username' => 'customer-user',
            'secret' => 'super-secret-password',
            'notes' => 'Sensitive notes',
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(route('customizations.show', $customizationRequest));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Customizations/Show')
                ->has('secureAccesses', 1)
                ->where(
                    'secureAccesses.0.id',
                    $secureAccess->id
                )
                ->where(
                    'secureAccesses.0.direction',
                    CustomizationSecureAccessDirection::AdminToCustomer->value
                )
                ->where(
                    'secureAccesses.0.status',
                    CustomizationSecureAccessStatus::Submitted->value
                )
                ->where(
                    'secureAccesses.0.label',
                    $secureAccess->label
                )
                ->missing('secureAccesses.0.login_url')
                ->missing('secureAccesses.0.username')
                ->missing('secureAccesses.0.secret')
                ->missing('secureAccesses.0.notes')
            );
    }

    public function test_admin_show_page_receives_secure_access_metadata_without_sensitive_values(): void
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
            'status' => CustomizationSecureAccessStatus::Submitted,
            'login_url' => 'https://hosting.example.com/login',
            'username' => 'hosting-user',
            'secret' => 'hosting-password',
            'notes' => 'Private hosting details',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                )
            );

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customizations/Show')
                ->has('secureAccesses', 1)
                ->where(
                    'secureAccesses.0.id',
                    $secureAccess->id
                )
                ->where(
                    'secureAccesses.0.direction',
                    CustomizationSecureAccessDirection::CustomerToAdmin->value
                )
                ->where(
                    'secureAccesses.0.status',
                    CustomizationSecureAccessStatus::Submitted->value
                )
                ->where(
                    'secureAccesses.0.label',
                    $secureAccess->label
                )
                ->missing('secureAccesses.0.login_url')
                ->missing('secureAccesses.0.username')
                ->missing('secureAccesses.0.secret')
                ->missing('secureAccesses.0.notes')
            );
    }
}