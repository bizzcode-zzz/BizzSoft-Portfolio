<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationRequestStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_admin_can_start_review_of_submitted_customization_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->status
        );
    }

    public function test_start_review_persists_under_review_status_in_database(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            )
            ->assertRedirect();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::UnderReview->value,
        ]);
    }

    public function test_admin_cannot_start_review_when_request_is_already_under_review(): void
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
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            );

        $response->assertUnprocessable();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->status
        );
    }

    public function test_admin_cannot_start_review_from_an_invalid_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Completed,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            );

        $response->assertUnprocessable();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Completed,
            $customizationRequest->status
        );
    }

    public function test_customer_cannot_start_review_of_own_customization_request(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($customer)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            );

        $response->assertForbidden();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    public function test_other_customer_cannot_start_review_of_customization_request(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $customizationRequest = CustomizationRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->patch(
                "/admin/customizations/{$customizationRequest->id}/start-review"
            );

        $response->assertForbidden();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    public function test_guest_cannot_start_review_of_customization_request(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this->patch(
            "/admin/customizations/{$customizationRequest->id}/start-review"
        );

        $response->assertRedirect(route('login'));

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->status
        );
    }

    public function test_admin_can_decline_customization_request_under_review(): void
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
                "/admin/customizations/{$customizationRequest->id}/decline",
                [
                    'message' => 'We cannot meet the requested requirements.',
                ]
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::RequestDeclined,
            $customizationRequest->status
        );
    }

    public function test_decline_reason_is_saved_in_customization_messages(): void
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
                "/admin/customizations/{$customizationRequest->id}/decline",
                [
                    'message' => 'The requested integration is not feasible.',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
            'message' => 'The requested integration is not feasible.',
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $customizationRequest->id,
            'status' => CustomizationRequestStatus::RequestDeclined->value,
        ]);
    }

    public function test_decline_requires_a_reason(): void
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
            ->from(
                "/admin/customizations/{$customizationRequest->id}"
            )
            ->post(
                "/admin/customizations/{$customizationRequest->id}/decline",
                [
                    'message' => '',
                ]
            );

        $response->assertRedirect(
            "/admin/customizations/{$customizationRequest->id}"
        );

        $response->assertSessionHasErrors('message');

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->status
        );

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_decline_customization_request_from_invalid_status(): void
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
                "/admin/customizations/{$customizationRequest->id}/decline",
                [
                    'message' => 'This should not be accepted.',
                ]
            );

        $response->assertUnprocessable();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::InProgress,
            $customizationRequest->status
        );

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $admin->id,
            'message' => 'This should not be accepted.',
        ]);
    }

    public function test_customer_cannot_decline_own_customization_request_as_admin(): void
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
                "/admin/customizations/{$customizationRequest->id}/decline",
                [
                    'message' => 'Customer should not be able to do this.',
                ]
            );

        $response->assertForbidden();

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->status
        );

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'user_id' => $customer->id,
            'message' => 'Customer should not be able to do this.',
        ]);
    }

    public function test_guest_cannot_decline_customization_request(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this->post(
            "/admin/customizations/{$customizationRequest->id}/decline",
            [
                'message' => 'Guest should not be able to do this.',
            ]
        );

        $response->assertRedirect(route('login'));

        $customizationRequest->refresh();

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->status
        );

        $this->assertDatabaseMissing('customization_messages', [
            'customization_request_id' => $customizationRequest->id,
            'message' => 'Guest should not be able to do this.',
        ]);
    }

    public function test_admin_can_decline_customization_request_while_waiting_for_customer_information(): void
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
            "/admin/customizations/{$customizationRequest->id}/decline",
            [
                'message' => 'The required information was not provided, so we cannot continue with this request.',
            ]
        );

    $response->assertRedirect(
        route('admin.customizations.show', $customizationRequest)
    );

    $customizationRequest->refresh();

    $this->assertSame(
        CustomizationRequestStatus::RequestDeclined,
        $customizationRequest->status
    );

    $this->assertDatabaseHas('customization_requests', [
        'id' => $customizationRequest->id,
        'status' => CustomizationRequestStatus::RequestDeclined->value,
    ]);

    $this->assertDatabaseHas('customization_messages', [
        'customization_request_id' => $customizationRequest->id,
        'user_id' => $admin->id,
        'message' => 'The required information was not provided, so we cannot continue with this request.',
    ]);
}
}