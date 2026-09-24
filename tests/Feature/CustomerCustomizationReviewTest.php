<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationMessage;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_customer_can_request_revision_for_own_request_ready_for_review(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => 'Please make the graph labels larger and add a monthly filter.',
                ]
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::RevisionRequested->value,
        ]);

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'Please make the graph labels larger and add a monthly filter.',
        ]);
    }

    public function test_revision_message_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($customer)
            ->from(route('customizations.show', $customizationRequest))
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => '',
                ]
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $response->assertSessionHasErrors('message');

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_cannot_request_revision_for_another_customers_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => 'Unauthorized revision request.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $otherCustomer->id,
        ]);
    }

    public function test_customer_cannot_request_revision_when_request_is_not_ready_for_review(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => 'Please revise this.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_can_approve_own_request_ready_for_review(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/approve"
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Completed->value,
        ]);
    }

    public function test_customer_cannot_approve_another_customers_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->patch(
                "/customizations/{$customizationRequest->id}/approve"
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }

    public function test_customer_cannot_approve_request_that_is_not_ready_for_review(): void
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
                "/customizations/{$customizationRequest->id}/approve"
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_customer_cannot_request_revision_twice(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $firstResponse = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => 'Please revise the graph.',
                ]
            );

        $firstResponse->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $secondResponse = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/request-revision",
                [
                    'message' => 'Another revision request.',
                ]
            );

        $secondResponse->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::RevisionRequested->value,
        ]);

        $this->assertSame(
            1,
            CustomizationMessage::query()
                ->where('customization_request_id', $customizationRequest->id)
                ->count()
        );
    }

    public function test_customer_cannot_approve_same_request_twice(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $firstResponse = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/approve"
            );

        $firstResponse->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $secondResponse = $this
            ->actingAs($customer)
            ->patch(
                "/customizations/{$customizationRequest->id}/approve"
            );

        $secondResponse->assertStatus(422);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::Completed->value,
        ]);
    }

    public function test_admin_cannot_use_customer_review_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $revisionRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $approvalRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $revisionResponse = $this
            ->actingAs($admin)
            ->post(
                "/customizations/{$revisionRequest->id}/request-revision",
                [
                    'message' => 'Admin should not use this action.',
                ]
            );

        $approvalResponse = $this
            ->actingAs($admin)
            ->patch(
                "/customizations/{$approvalRequest->id}/approve"
            );

        $revisionResponse->assertForbidden();
        $approvalResponse->assertForbidden();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $revisionRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $approvalRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }

    public function test_guest_cannot_use_customer_review_actions(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $revisionRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $approvalRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $revisionResponse = $this->post(
            "/customizations/{$revisionRequest->id}/request-revision",
            [
                'message' => 'Guest revision attempt.',
            ]
        );

        $approvalResponse = $this->patch(
            "/customizations/{$approvalRequest->id}/approve"
        );

        $revisionResponse->assertRedirect('/login');
        $approvalResponse->assertRedirect('/login');

        $this->assertDatabaseHas('customization_requests', [
            'id' => $revisionRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $approvalRequest->id,
            'status' => CustomizationRequestStatus::ReadyForReview->value,
        ]);
    }
}