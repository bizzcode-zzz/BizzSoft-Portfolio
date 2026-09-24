<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCustomizationQuoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_admin_can_send_quote_for_request_under_review(): void
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
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '12500.00',
                    'scope' => 'Build a monthly product sales graph for the customer dashboard.',
                    'estimated_delivery' => now()->addDays(14)->toDateString(),
                ]
            );

        $response->assertRedirect(
            route('admin.customizations.show', $customizationRequest)
        );

        $this->assertDatabaseHas('customization_quotes', [
            'customization_request_id' => $customizationRequest->id,
            'price' => 12500.00,
            'scope' => 'Build a monthly product sales graph for the customer dashboard.',
        ]);

        $this->assertSame(
            CustomizationRequestStatus::QuoteSent,
            $customizationRequest->fresh()->status
        );
    }

    public function test_quote_is_attached_to_correct_customization_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $requestOne = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $requestTwo = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$requestOne->id}/quote",
                [
                    'price' => '5000.00',
                    'scope' => 'Customization for request one.',
                    'estimated_delivery' => now()->addWeek()->toDateString(),
                    'customization_request_id' => $requestTwo->id,
                ]
            )
            ->assertRedirect();

        $quote = CustomizationQuote::query()->sole();

        $this->assertSame(
            $requestOne->id,
            $quote->customization_request_id
        );

        $this->assertNotSame(
            $requestTwo->id,
            $quote->customization_request_id
        );
    }

    public function test_price_is_required_and_must_be_greater_than_zero(): void
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
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => 0,
                    'scope' => 'Valid scope.',
                    'estimated_delivery' => now()->addWeek()->toDateString(),
                ]
            );

        $response
            ->assertRedirect(route('admin.customizations.show', $customizationRequest))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('customization_quotes', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_scope_is_required(): void
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
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '5000.00',
                    'scope' => '',
                    'estimated_delivery' => now()->addWeek()->toDateString(),
                ]
            );

        $response
            ->assertRedirect(route('admin.customizations.show', $customizationRequest))
            ->assertSessionHasErrors('scope');

        $this->assertDatabaseCount('customization_quotes', 0);
    }

    public function test_estimated_delivery_must_be_after_today(): void
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
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '5000.00',
                    'scope' => 'Valid quotation scope.',
                    'estimated_delivery' => now()->toDateString(),
                ]
            );

        $response
            ->assertRedirect(route('admin.customizations.show', $customizationRequest))
            ->assertSessionHasErrors('estimated_delivery');

        $this->assertDatabaseCount('customization_quotes', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_send_quote_when_request_is_not_under_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '5000.00',
                    'scope' => 'This should not be created.',
                    'estimated_delivery' => now()->addWeek()->toDateString(),
                ]
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('customization_quotes', 0);

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $customizationRequest->fresh()->status
        );
    }

    public function test_admin_cannot_create_second_quote_for_same_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '8000.00',
                    'scope' => 'Second quotation.',
                    'estimated_delivery' => now()->addDays(10)->toDateString(),
                ]
            );

        $response->assertUnprocessable();

        $this->assertDatabaseCount('customization_quotes', 1);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_customer_cannot_use_admin_quote_route(): void
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
                "/admin/customizations/{$customizationRequest->id}/quote",
                [
                    'price' => '5000.00',
                    'scope' => 'Customer should not create this.',
                    'estimated_delivery' => now()->addWeek()->toDateString(),
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('customization_quotes', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }

    public function test_guest_cannot_send_quote(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $response = $this->post(
            "/admin/customizations/{$customizationRequest->id}/quote",
            [
                'price' => '5000.00',
                'scope' => 'Guest should not create this.',
                'estimated_delivery' => now()->addWeek()->toDateString(),
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('customization_quotes', 0);

        $this->assertSame(
            CustomizationRequestStatus::UnderReview,
            $customizationRequest->fresh()->status
        );
    }
}