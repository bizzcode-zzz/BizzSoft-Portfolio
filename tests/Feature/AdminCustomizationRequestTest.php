<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationMessage;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
            ->has('customizationRequests.data', 2)
            ->where(
                'customizationRequests.data.0.id',
                $secondRequest->id,
            )
            ->where(
                'customizationRequests.data.1.id',
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
                'customizationRequests.data.0.user.name',
                'Test Customer',
            )
            ->where(
                'customizationRequests.data.0.user.email',
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

    public function test_admin_conversation_history_is_paginated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        foreach (range(1, 55) as $number) {
            CustomizationMessage::factory()->create([
                'customization_request_id' => $customizationRequest->id,
                'user_id' => $customer->id,
                'message' => "Message {$number}",
            ]);
        }

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Customizations/Show')
                    ->has('messageHistory.data', 50)
                    ->where('messageHistory.current_page', 1)
                    ->where('messageHistory.data.0.message', 'Message 6')
                    ->where('messageHistory.data.49.message', 'Message 55')
            );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                ).'?messages_page=2'
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Customizations/Show')
                    ->has('messageHistory.data', 5)
                    ->where('messageHistory.current_page', 2)
                    ->where('messageHistory.data.0.message', 'Message 1')
                    ->where('messageHistory.data.4.message', 'Message 5')
            );
    }
    public function test_admin_marks_only_visible_incoming_messages_as_read(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        foreach (range(1, 55) as $number) {
            CustomizationMessage::factory()->create([
                'customization_request_id' => $customizationRequest->id,
                'user_id' => $customer->id,
                'message' => "Unread customer message {$number}",
                'read_at' => null,
            ]);
        }

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk();

        $this->assertSame(
            50,
            CustomizationMessage::query()
                ->where('customization_request_id', $customizationRequest->id)
                ->whereNotNull('read_at')
                ->count()
        );

        $this->assertSame(
            5,
            CustomizationMessage::query()
                ->where('customization_request_id', $customizationRequest->id)
                ->whereNull('read_at')
                ->count()
        );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                ).'?messages_page=2'
            )
            ->assertOk();

        $this->assertSame(
            55,
            CustomizationMessage::query()
                ->where('customization_request_id', $customizationRequest->id)
                ->whereNotNull('read_at')
                ->count()
        );

        $this->assertSame(
            0,
            CustomizationMessage::query()
                ->where('customization_request_id', $customizationRequest->id)
                ->whereNull('read_at')
                ->count()
        );
    }
    public function test_admin_show_reports_unpaid_accepted_quote_state(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'price' => '450.00',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('quotePayment.price', '450.00')
                    ->where('quotePayment.verified_amount', '0.00')
                    ->where('quotePayment.usable_verified_amount', '0.00')
                    ->where('quotePayment.remaining_amount', '450.00')
                    ->where('quotePayment.has_active_hold', false)
                    ->where('quotePayment.fully_paid_and_unheld', false)
            );
    }

    public function test_admin_show_reports_fully_paid_accepted_quote_state(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $quote = CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'price' => '450.00',
        ]);

        $quote->payments()->create([
            'payment_number' => 'PAY-ADMIN-SHOW-FULL-001',
            'user_id' => $customer->id,
            'amount' => '450.00',
            'currency' => 'USD',
            'provider' => 'manual',
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('quotePayment.price', '450.00')
                    ->where('quotePayment.verified_amount', '450.00')
                    ->where('quotePayment.usable_verified_amount', '450.00')
                    ->where('quotePayment.remaining_amount', '0.00')
                    ->where('quotePayment.has_active_hold', false)
                    ->where('quotePayment.fully_paid_and_unheld', true)
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
