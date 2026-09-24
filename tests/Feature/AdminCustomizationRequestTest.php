<?php

namespace Tests\Feature;

use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_admin_can_view_customization_request_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/admin/customizations');

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customizations/Index')
        );
    }

    public function test_admin_index_contains_requests_from_all_customers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $firstCustomer = User::factory()->create();
        $firstCustomer->assignRole('customer');

        $secondCustomer = User::factory()->create();
        $secondCustomer->assignRole('customer');

        $firstRequest = CustomizationRequest::factory()->create([
            'user_id' => $firstCustomer->id,
            'title' => 'First Customer Request',
        ]);

        $secondRequest = CustomizationRequest::factory()->create([
            'user_id' => $secondCustomer->id,
            'title' => 'Second Customer Request',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/customizations');

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customizations/Index')
            ->has('customizationRequests', 2)
            ->where(
                'customizationRequests.0.id',
                $secondRequest->id,
            )
            ->where(
                'customizationRequests.1.id',
                $firstRequest->id,
            )
        );
    }

    public function test_admin_index_includes_customer_information(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $customer->assignRole('customer');

        CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/customizations');

        $response->assertInertia(fn ($page) => $page
            ->where(
                'customizationRequests.0.user.name',
                'Test Customer',
            )
            ->where(
                'customizationRequests.0.user.email',
                'customer@example.com',
            )
        );
    }

    public function test_admin_can_view_any_customer_customization_request(): void
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
            ->get("/admin/customizations/{$customizationRequest->id}");

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customizations/Show')
            ->where(
                'customizationRequest.id',
                $customizationRequest->id,
            )
            ->where(
                'customizationRequest.user.id',
                $customer->id,
            )
        );
    }

    public function test_customer_cannot_access_admin_customization_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/admin/customizations');

        $response->assertForbidden();
    }

    public function test_customer_cannot_access_admin_customization_show_page(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->get("/admin/customizations/{$customizationRequest->id}");

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_admin_customization_routes(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();

        $this
            ->get('/admin/customizations')
            ->assertRedirect(route('login'));

        $this
            ->get("/admin/customizations/{$customizationRequest->id}")
            ->assertRedirect(route('login'));
    }
}