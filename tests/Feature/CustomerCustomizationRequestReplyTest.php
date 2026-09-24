<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationMessage;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerCustomizationRequestReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_customer_can_reply_when_own_request_needs_information(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => 'I would like a monthly bar graph using product sales totals.',
                ]
            );

        $response->assertRedirect(
            route('customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'I would like a monthly bar graph using product sales totals.',
        ]);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_reply_sender_is_authenticated_customer_and_cannot_be_spoofed(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherUser = User::factory()->create();

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $this
            ->actingAs($customer)
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => 'Here are the requested details.',
                    'user_id' => $otherUser->id,
                ]
            )
            ->assertRedirect();

        $message = CustomizationMessage::query()->sole();

        $this->assertSame($customer->id, $message->user_id);

        $this->assertSame(
            $customizationRequest->id,
            $message->customization_request_id
        );
    }

    public function test_reply_message_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($customer)
            ->from(route('customizations.show', $customizationRequest))
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                []
            );

        $response
            ->assertRedirect(route('customizations.show', $customizationRequest))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }

    public function test_reply_message_cannot_exceed_5000_characters(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($customer)
            ->from(route('customizations.show', $customizationRequest))
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => str_repeat('a', 5001),
                ]
            );

        $response
            ->assertRedirect(route('customizations.show', $customizationRequest))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_reply_when_request_is_under_review(): void
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
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => 'This reply should not be accepted.',
                ]
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_reply_to_another_customers_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => 'I should not be allowed to reply.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_use_customer_reply_route(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/customizations/{$customizationRequest->id}/replies",
                [
                    'message' => 'Admin should not use this route.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }

    public function test_guest_cannot_reply_to_customization_request(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this->post(
            "/customizations/{$customizationRequest->id}/replies",
            [
                'message' => 'Guest reply.',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }
}