<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizationRequestFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_request_can_be_created(): void
    {
        $user = User::factory()->create();

        $request = CustomizationRequest::create([
            'user_id' => $user->id,
            'title' => 'Custom Dashboard',
            'description' => 'I need a customized dashboard for my project.',
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $this->assertDatabaseHas('customization_requests', [
            'id' => $request->id,
            'user_id' => $user->id,
            'title' => 'Custom Dashboard',
            'status' => CustomizationRequestStatus::Submitted->value,
        ]);
    }

    public function test_customization_request_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $request = CustomizationRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($request->user->is($user));
    }

    public function test_status_is_cast_to_customization_request_status_enum(): void
    {
        $request = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $this->assertInstanceOf(
            CustomizationRequestStatus::class,
            $request->status,
        );

        $this->assertSame(
            CustomizationRequestStatus::InProgress,
            $request->status,
        );
    }

    public function test_database_default_status_is_submitted(): void
    {
        $user = User::factory()->create();

        $request = CustomizationRequest::query()->create([
            'user_id' => $user->id,
            'title' => 'Payment Gateway Customization',
            'description' => 'Please customize the payment workflow.',
        ]);

        $request->refresh();

        $this->assertSame(
            CustomizationRequestStatus::Submitted,
            $request->status,
        );

        $this->assertDatabaseHas('customization_requests', [
            'id' => $request->id,
            'status' => CustomizationRequestStatus::Submitted->value,
        ]);
    }

    public function test_factory_creates_a_valid_customization_request(): void
    {
        $request = CustomizationRequest::factory()->create();

        $this->assertDatabaseHas('customization_requests', [
            'id' => $request->id,
            'user_id' => $request->user_id,
        ]);

        $this->assertInstanceOf(
            CustomizationRequestStatus::class,
            $request->status,
        );
    }

    public function test_status_enum_has_expected_labels(): void
    {
        $this->assertSame(
            'Submitted',
            CustomizationRequestStatus::Submitted->label(),
        );

        $this->assertSame(
            'Under Review',
            CustomizationRequestStatus::UnderReview->label(),
        );

        $this->assertSame(
            'Needs Information',
            CustomizationRequestStatus::NeedsInformation->label(),
        );

        $this->assertSame(
            'Quote Sent',
            CustomizationRequestStatus::QuoteSent->label(),
        );

        $this->assertSame(
            'In Progress',
            CustomizationRequestStatus::InProgress->label(),
        );

        $this->assertSame(
            'Ready for Review',
            CustomizationRequestStatus::ReadyForReview->label(),
        );

        $this->assertSame(
            'Revision Requested',
            CustomizationRequestStatus::RevisionRequested->label(),
        );

        $this->assertSame(
            'Completed',
            CustomizationRequestStatus::Completed->label(),
        );
    }
}