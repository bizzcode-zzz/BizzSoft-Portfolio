<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomizationConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_customer_can_send_normal_message_without_changing_status(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'Additional note: please add a monthly filter.',
                ]
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'Additional note: please add a monthly filter.',
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::UnderReview->value,
        ]);
    }

    public function test_admin_can_send_normal_message_without_changing_status(): void
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
            ->post(
                "/admin/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'The requested graph changes are now in progress.',
                ]
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
            'message' => 'The requested graph changes are now in progress.',
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::InProgress->value,
        ]);
    }

    public function test_customer_can_send_multiple_normal_messages(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'First additional detail.',
                ]
            )
            ->assertRedirect();

        $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'Second detail that I forgot earlier.',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'First additional detail.',
        ]);

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'Second detail that I forgot earlier.',
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::UnderReview->value,
        ]);
    }

    public function test_customer_cannot_message_another_customers_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'I should not be able to send this.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $otherCustomer->id,
        ]);
    }

    public function test_message_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($customer)
            ->from("/customizations/{$customizationRequest->id}")
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => '',
                ]
            );

        $response->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_cannot_send_normal_message_after_completion(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Completed,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'This should not be saved.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_admin_cannot_send_normal_message_after_request_is_declined(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::RequestDeclined,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'This should not be saved.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_customer_cannot_send_normal_message_after_cancellation(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Cancelled,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'This should not be saved.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_cannot_send_normal_message_after_quote_is_declined(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteDeclined,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/messages",
                [
                    'message' => 'This should not be saved.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_guest_cannot_send_customer_conversation_message(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this->post(
            "/customizations/{$customizationRequest->id}/messages",
            [
                'message' => 'Guest message.',
            ]
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'message' => 'Guest message.',
        ]);
    }

    public function test_guest_cannot_send_admin_conversation_message(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this->post(
            "/admin/customizations/{$customizationRequest->id}/messages",
            [
                'message' => 'Guest admin message.',
            ]
        );

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'message' => 'Guest admin message.',
        ]);
    }
}