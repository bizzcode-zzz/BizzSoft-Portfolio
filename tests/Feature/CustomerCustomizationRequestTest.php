<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_customer_can_view_customization_request_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/customizations');

        $response->assertOk();
    }

    public function test_customer_index_only_contains_their_own_requests(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $ownRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'title' => 'My Customization',
        ]);

        $otherRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
            'title' => 'Other Customer Customization',
        ]);

        $response = $this
            ->actingAs($customer)
            ->get('/customizations');

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Customizations/Index')
            ->has('customizationRequests', 1)
            ->where('customizationRequests.0.id', $ownRequest->id)
            ->where('customizationRequests.0.title', 'My Customization')
        );

        $this->assertNotSame($ownRequest->id, $otherRequest->id);
    }

    public function test_customer_can_view_create_page(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/customizations/create');

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Customizations/Create')
        );
    }

    public function test_customer_can_create_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/customizations', [
                'title' => 'Custom Reporting Dashboard',
                'description' => 'I need a custom reporting dashboard.',
            ]);

        $customizationRequest = CustomizationRequest::query()->first();

        $this->assertNotNull($customizationRequest);

        $this->assertSame(
            $customer->id,
            $customizationRequest->user_id,
        );

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status,
        );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );
    }

    public function test_customer_cannot_spoof_request_owner(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $this
            ->actingAs($customer)
            ->post('/customizations', [
                'user_id' => $otherCustomer->id,
                'title' => 'Spoofed Request',
                'description' => 'Attempting to spoof ownership.',
            ]);

        $customizationRequest = CustomizationRequest::query()->first();

        $this->assertNotNull($customizationRequest);

        $this->assertSame(
            $customer->id,
            $customizationRequest->user_id,
        );

        $this->assertNotSame(
            $otherCustomer->id,
            $customizationRequest->user_id,
        );
    }

    public function test_customer_cannot_choose_initial_status(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this
            ->actingAs($customer)
            ->post('/customizations', [
                'title' => 'Status Spoof Test',
                'description' => 'Attempting to choose the initial status.',
                'status' => CustomizationRequestStatus::Completed->value,
            ]);

        $customizationRequest = CustomizationRequest::query()->first();

        $this->assertNotNull($customizationRequest);

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status,
        );
    }

    public function test_title_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/customizations', [
                'description' => 'Customization details.',
            ]);

        $response->assertSessionHasErrors('title');

        $this->assertDatabaseCount('customization_requests', 0);
    }

    public function test_description_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/customizations', [
                'title' => 'Custom Dashboard',
            ]);

        $response->assertSessionHasErrors('description');

        $this->assertDatabaseCount('customization_requests', 0);
    }

    public function test_customer_can_view_own_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->get("/customizations/{$customizationRequest->id}");

        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Customizations/Show')
            ->where(
                'customizationRequest.id',
                $customizationRequest->id,
            )
        );
    }

    public function test_customer_cannot_view_another_customers_request(): void
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
            ->get("/customizations/{$customizationRequest->id}");

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_customer_customization_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/customizations');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/customizations');

        $response->assertRedirect(route('login'));
    }
}