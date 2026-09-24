<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationMessage;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationRequestInformationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_admin_can_request_information_from_under_review_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => 'What type of graph would you like us to build?',
                ]
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->status
        );

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
            'message' => 'What type of graph would you like us to build?',
        ]);
    }

    public function test_request_information_message_is_sent_by_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => 'Please provide additional details.',
                    'user_id' => $customer->id,
                ]
            )
            ->assertRedirect();

        $message = CustomizationMessage::query()->sole();

        $this->assertSame($admin->id, $message->user_id);
        $this->assertSame(
            $customizationRequest->id,
            $message->customization_request_id
        );
    }

    public function test_message_is_required_when_requesting_information(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.customizations.show', $customizationRequest))
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                []
            );

        $response
            ->assertRedirect(route('admin.customizations.show', $customizationRequest))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_message_cannot_exceed_5000_characters(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.customizations.show', $customizationRequest))
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => str_repeat('a', 5001),
                ]
            );

        $response
            ->assertRedirect(route('admin.customizations.show', $customizationRequest))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_request_information_from_submitted_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => 'Please provide more information.',
                ]
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_request_information_when_request_already_needs_information(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => 'Another question.',
                ]
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::NeedsInformation,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_request_information(): void
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
                "/admin/customizations/{$customizationRequest->id}/request-information",
                [
                    'message' => 'Unauthorized message.',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_guest_cannot_request_information(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this->post(
            "/admin/customizations/{$customizationRequest->id}/request-information",
            [
                'message' => 'Guest message.',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('customization_messages', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }
}