<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationQuoteDecisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_customer_can_accept_quote_for_own_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $this->assertSame(
            CustomizationRequestStatus::Accepted,
            $customizationRequest->fresh()->status
        );

        $this->assertDatabaseHas('customization_quotes', [
            'customization_request_id' => $customizationRequest->id,
        ]);
    }

    public function test_customer_can_decline_quote_for_own_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/decline"
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $this->assertSame(
            CustomizationRequestStatus::QuoteDeclined,
            $customizationRequest->fresh()->status
        );

        $this->assertDatabaseHas('customization_quotes', [
            'customization_request_id' => $customizationRequest->id,
        ]);
    }

    public function test_customer_cannot_accept_quote_for_another_customers_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            );

        $response->assertForbidden();

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_decline_quote_for_another_customers_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $otherCustomer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/decline"
            );

        $response->assertForbidden();

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_quote_cannot_be_accepted_when_status_is_not_quote_sent(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            );

        $response->assertUnprocessable();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_quote_cannot_be_declined_when_status_is_not_quote_sent(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/decline"
            );

        $response->assertUnprocessable();

        $this->assertSame(
            CustomizationRequestStatus::Accepted,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_decide_request_without_quote(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            );

        $response->assertUnprocessable();

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_decide_same_quote_twice(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            )
            ->assertRedirect();

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/decline"
            );

        $response->assertUnprocessable();

        $this->assertSame(
            CustomizationRequestStatus::Accepted,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_use_customer_quote_decision_route(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/customizations/{$customizationRequest->id}/quote/accept"
            );

        $response->assertForbidden();

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_guest_cannot_accept_quote(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this->patch(
            "/customizations/{$customizationRequest->id}/quote/accept"
        );

        $response->assertRedirect(route('login'));

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_guest_cannot_decline_quote(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this->patch(
            "/customizations/{$customizationRequest->id}/quote/decline"
        );

        $response->assertRedirect(route('login'));

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }
}