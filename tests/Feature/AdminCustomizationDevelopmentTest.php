<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationDevelopmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_admin_can_start_development_for_accepted_request_with_quote_and_verified_payment(): void
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
        ]);

        $this->createVerifiedPaymentForQuote(
            quote: $quote,
            customer: $customer,
            admin: $admin,
            paymentNumber: 'PAY-DEV-VERIFIED-001',
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_admin_cannot_start_development_without_verified_payment(): void
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
        ]);

        $quote->payments()->create([
            'payment_number' => 'PAY-DEV-PENDING-001',
            'user_id' => $customer->id,
            'amount' => $quote->price,
            'currency' => 'PHP',
            'provider' => 'card_gateway',
            'method' => 'card',
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Accepted->value,
        ]);
    }

    public function test_admin_cannot_start_development_when_request_is_not_accepted(): void
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
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::QuoteSent->value,
        ]);
    }

    public function test_admin_cannot_start_development_without_quote(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Accepted->value,
        ]);
    }

    public function test_customer_cannot_start_development(): void
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
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Accepted->value,
        ]);
    }

    public function test_guest_cannot_start_development(): void
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

        $response = $this->patch(
            "/admin/customizations/{$customizationRequest->id}/start-development"
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Accepted->value,
        ]);
    }

    public function test_start_development_cannot_be_performed_twice(): void
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
        ]);

        $this->createVerifiedPaymentForQuote(
            quote: $quote,
            customer: $customer,
            admin: $admin,
            paymentNumber: 'PAY-DEV-VERIFIED-002',
        );

        $firstResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $firstResponse->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $secondResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-development"
            );

        $secondResponse->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_admin_can_mark_in_progress_request_ready_for_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/ready-for-review"
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }

    public function test_admin_cannot_mark_request_ready_for_review_when_not_in_progress(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/ready-for-review"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Accepted->value,
        ]);
    }

    public function test_customer_cannot_mark_request_ready_for_review(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/ready-for-review"
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_ready_for_review_transition_cannot_be_performed_twice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $firstResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/ready-for-review"
            );

        $firstResponse->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $secondResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/ready-for-review"
            );

        $secondResponse->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }

    public function test_admin_can_resume_development_after_revision_is_requested(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/resume-development"
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_admin_cannot_resume_development_when_revision_is_not_requested(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/resume-development"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }

    public function test_customer_cannot_resume_development(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/resume-development"
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::RevisionRequested->value,
        ]);
    }

    public function test_guest_cannot_resume_development(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $response = $this->patch(
            "/admin/customizations/{$customizationRequest->id}/resume-development"
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::RevisionRequested->value,
        ]);
    }

    public function test_resume_development_cannot_be_performed_twice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $firstResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/resume-development"
            );

        $firstResponse->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $secondResponse = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/resume-development"
            );

        $secondResponse->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    private function createVerifiedPaymentForQuote(
        CustomizationQuote $quote,
        User $customer,
        User $admin,
        string $paymentNumber,
    ): void {
        $quote->payments()->create([
            'payment_number' => $paymentNumber,
            'user_id' => $customer->id,
            'amount' => $quote->price,
            'currency' => 'PHP',
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'status' => PaymentStatus::Verified,
            'submitted_at' => now(),
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);
    }
}