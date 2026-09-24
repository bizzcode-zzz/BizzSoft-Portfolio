<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_customer_can_cancel_own_submitted_request(): void
    {
        $this->assertCustomerCanCancelFromStatus(
            CustomizationRequestStatus::Submitted
        );
    }

    public function test_customer_can_cancel_own_request_under_review(): void
    {
        $this->assertCustomerCanCancelFromStatus(
            CustomizationRequestStatus::UnderReview
        );
    }

    public function test_customer_can_cancel_own_request_needing_information(): void
    {
        $this->assertCustomerCanCancelFromStatus(
            CustomizationRequestStatus::NeedsInformation
        );
    }

    public function test_customer_can_cancel_own_request_after_quote_is_sent(): void
    {
        $this->assertCustomerCanCancelFromStatus(
            CustomizationRequestStatus::QuoteSent
        );
    }

    public function test_customer_can_cancel_own_accepted_request_before_development_starts(): void
    {
        $this->assertCustomerCanCancelFromStatus(
            CustomizationRequestStatus::Accepted
        );
    }

    public function test_customer_cannot_cancel_request_once_development_is_in_progress(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::InProgress
        );
    }

    public function test_customer_cannot_cancel_request_ready_for_review(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::ReadyForReview
        );
    }

    public function test_customer_cannot_cancel_request_when_revision_is_requested(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::RevisionRequested
        );
    }

    public function test_customer_cannot_cancel_completed_request(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::Completed
        );
    }

    public function test_customer_cannot_cancel_admin_declined_request(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::RequestDeclined
        );
    }

    public function test_customer_cannot_cancel_quote_declined_request(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::QuoteDeclined
        );
    }

    public function test_customer_cannot_cancel_already_cancelled_request(): void
    {
        $this->assertCustomerCannotCancelFromStatus(
            CustomizationRequestStatus::Cancelled
        );
    }

    public function test_customer_cannot_cancel_another_customers_request(): void
    {
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->patch(
                route('customizations.cancel', $customizationRequest)
            );

        $response->assertForbidden();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    public function test_admin_cannot_use_customer_cancel_route(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = $this->makeCustomer();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('customizations.cancel', $customizationRequest)
            );

        $response->assertForbidden();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    public function test_guest_is_redirected_to_login_when_attempting_to_cancel(): void
    {
        $customer = $this->makeCustomer();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this->patch(
            route('customizations.cancel', $customizationRequest)
        );

        $response->assertRedirect(route('login'));

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    private function assertCustomerCanCancelFromStatus(
        CustomizationRequestStatus $status
    ): void {
        $customer = $this->makeCustomer();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => $status,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                route('customizations.cancel', $customizationRequest)
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $customizationRequest->status
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Cancelled->value,
        ]);
    }

    private function assertCustomerCannotCancelFromStatus(
        CustomizationRequestStatus $status
    ): void {
        $customer = $this->makeCustomer();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => $status,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                route('customizations.cancel', $customizationRequest)
            );

        $response->assertStatus(422);

        $customizationRequest->refresh();

        $this->assertSame(
            $status,
            $customizationRequest->status
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => $status->value,
        ]);
    }

    private function makeCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }
}