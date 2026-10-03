<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationMessage;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
            ->has('customizationRequests.data', 1)
            ->where('customizationRequests.data.0.id', $ownRequest->id)
            ->where('customizationRequests.data.0.title', 'My Customization')
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

    public function test_customer_conversation_history_is_paginated(): void
    {
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
            ->actingAs($customer)
            ->get(route('customizations.show', $customizationRequest))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Customizations/Show')
                    ->has('messageHistory.data', 50)
                    ->where('messageHistory.current_page', 1)
                    ->where('messageHistory.data.0.message', 'Message 6')
                    ->where('messageHistory.data.49.message', 'Message 55')
            );

        $this
            ->actingAs($customer)
            ->get(
                route('customizations.show', $customizationRequest)
                    .'?messages_page=2'
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Customizations/Show')
                    ->has('messageHistory.data', 5)
                    ->where('messageHistory.current_page', 2)
                    ->where('messageHistory.data.0.message', 'Message 1')
                    ->where('messageHistory.data.4.message', 'Message 5')
            );
    }
    public function test_customer_marks_only_visible_incoming_messages_as_read(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
        ]);

        foreach (range(1, 55) as $number) {
            CustomizationMessage::factory()->create([
                'customization_request_id' => $customizationRequest->id,
                'user_id' => $admin->id,
                'message' => "Unread admin message {$number}",
                'read_at' => null,
            ]);
        }

        $this
            ->actingAs($customer)
            ->get(route('customizations.show', $customizationRequest))
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
            ->actingAs($customer)
            ->get(
                route('customizations.show', $customizationRequest)
                    .'?messages_page=2'
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
    public function test_customer_payment_summary_respects_active_hold_and_restoration(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $quote = $customizationRequest->quote()->create([
            'price' => '100.00',
            'scope' => 'Hold-aware customer payment summary.',
            'estimated_delivery' => now()
                ->addWeek()
                ->toDateString(),
        ]);

        $payment = $quote->payments()->create([
            'payment_number' => 'PAY-CUSTOMER-HOLD-SUMMARY-001',
            'user_id' => $customer->id,
            'amount' => '100.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'provider_payment_id' => 'txn_customer_hold_summary_001',
            'status' => \App\Enums\PaymentStatus::Verified,
            'submitted_at' => now(),
            'verified_at' => now(),
        ]);

        $adjustment = \App\Models\PaymentAdjustment::create([
            'provider' => 'paddle',
            'provider_adjustment_id' => 'adj_customer_hold_summary_001',
            'provider_transaction_id' => $payment->provider_payment_id,
            'payment_id' => $payment->id,
            'order_id' => null,
            'provider_updated_at' => now(),
            'last_event_id' => 'evt_customer_hold_summary_001',
            'snapshot' => [],
            'history' => [],
            'hold_active' => true,
            'review_required' => false,
            'decision' => 'automatic_hold',
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'paymentSummary.verified_amount',
                        '100.00'
                    )
                    ->where(
                        'paymentSummary.remaining_amount',
                        '0.00'
                    )
                    ->where(
                        'paymentSummary.fully_paid',
                        true
                    )
                    ->where(
                        'paymentSummary.has_active_hold',
                        true
                    )
                    ->where(
                        'paymentSummary.fully_paid_and_unheld',
                        false
                    )
            );

        $adjustment->update([
            'hold_active' => false,
            'decision' => 'restored',
        ]);

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customizations.show',
                    $customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'paymentSummary.verified_amount',
                        '100.00'
                    )
                    ->where(
                        'paymentSummary.remaining_amount',
                        '0.00'
                    )
                    ->where(
                        'paymentSummary.fully_paid',
                        true
                    )
                    ->where(
                        'paymentSummary.has_active_hold',
                        false
                    )
                    ->where(
                        'paymentSummary.fully_paid_and_unheld',
                        true
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
