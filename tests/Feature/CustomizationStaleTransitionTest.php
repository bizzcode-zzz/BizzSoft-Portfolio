<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Admin\CustomizationDevelopmentController;
use App\Http\Controllers\Admin\CustomizationQuoteController;
use App\Http\Controllers\Admin\CustomizationRequestInformationController;
use App\Http\Controllers\Admin\CustomizationRequestStatusController;
use App\Http\Controllers\CustomizationCancellationController;
use App\Http\Controllers\CustomizationConversationController;
use App\Http\Controllers\CustomizationQuoteDecisionController;
use App\Http\Controllers\CustomizationRequestReplyController;
use App\Http\Controllers\CustomizationReviewController;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class CustomizationStaleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
    }

    public function test_stale_start_development_cannot_overwrite_cancelled_request(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $quote = CustomizationQuote::factory()->create([
            'customization_request_id' => $customization->id,
        ]);

        $quote->payments()->create([
            'payment_number' => 'PAY-STALE-DEV-001',
            'user_id' => $customer->id,
            'amount' => $quote->price,
            'currency' => 'USD',
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'status' => PaymentStatus::Verified,
            'submitted_at' => now(),
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Cancelled
        );

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationDevelopmentController::class)
                ->start($stale, app(\App\Payments\PaymentEntitlements::class))
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Cancelled
        );
    }

    public function test_stale_ready_for_review_cannot_overwrite_revision_requested(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::RevisionRequested
        );

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationDevelopmentController::class)
                ->readyForReview($stale, app(\App\Payments\PaymentEntitlements::class))
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::RevisionRequested
        );
    }

    public function test_stale_resume_cannot_overwrite_completed_request(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Completed
        );

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationDevelopmentController::class)
                ->resume($stale, app(\App\Payments\PaymentEntitlements::class))
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Completed
        );
    }

    public function test_stale_quote_creation_cannot_overwrite_newer_status(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::NeedsInformation
        );

        $request = $this->requestFor($admin, [
            'price' => '5000.00',
            'scope' => 'This stale quote must not be created.',
            'estimated_delivery' => now()->addWeek()->toDateString(),
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationQuoteController::class)
                ->store($request, $stale)
        );

        $this->assertDatabaseMissing('customization_quotes', [
            'customization_request_id' => $customization->id,
        ]);

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::NeedsInformation
        );
    }

    public function test_stale_request_information_cannot_overwrite_quote_sent(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::QuoteSent
        );

        $request = $this->requestFor($admin, [
            'message' => 'Stale information request.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationRequestInformationController::class)
                ->store($request, $stale)
        );

        $this->assertNoMessage(
            $customization,
            'Stale information request.'
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::QuoteSent
        );
    }

    public function test_stale_start_review_cannot_overwrite_cancelled_request(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::Submitted,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Cancelled
        );

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationRequestStatusController::class)
                ->startReview($stale)
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Cancelled
        );
    }

    public function test_stale_admin_decline_cannot_overwrite_quote_sent(): void
    {
        $admin = $this->admin();

        $customization = CustomizationRequest::factory()->create([
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::QuoteSent
        );

        $request = $this->requestFor($admin, [
            'message' => 'Stale decline.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationRequestStatusController::class)
                ->decline($request, $stale)
        );

        $this->assertNoMessage(
            $customization,
            'Stale decline.'
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::QuoteSent
        );
    }

    public function test_stale_customer_cancel_cannot_overwrite_in_progress(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::InProgress
        );

        $this->actingAs($customer);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationCancellationController::class)
                ->cancel($stale)
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::InProgress
        );
    }

    public function test_stale_quote_accept_cannot_overwrite_cancelled_request(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::QuoteSent,
        ]);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customization->id,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Cancelled
        );

        $this->actingAs($customer);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationQuoteDecisionController::class)
                ->accept($stale)
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Cancelled
        );
    }

    public function test_stale_customer_information_reply_cannot_reopen_declined_request(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::NeedsInformation,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::RequestDeclined
        );

        $request = $this->requestFor($customer, [
            'message' => 'Stale customer information.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationRequestReplyController::class)
                ->store($request, $stale)
        );

        $this->assertNoMessage(
            $customization,
            'Stale customer information.'
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::RequestDeclined
        );
    }

    public function test_stale_revision_request_cannot_overwrite_completed_request(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Completed
        );

        $request = $this->requestFor($customer, [
            'message' => 'Stale revision request.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationReviewController::class)
                ->requestRevision($request, $stale)
        );

        $this->assertNoMessage(
            $customization,
            'Stale revision request.'
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Completed
        );
    }

    public function test_stale_approval_cannot_overwrite_revision_requested(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::RevisionRequested
        );

        $this->actingAs($customer);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationReviewController::class)
                ->approve(
                    app(\Illuminate\Http\Request::class),
                    $stale,
                    app(\App\Payments\PaymentEntitlements::class)
                )
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::RevisionRequested
        );
    }

    public function test_stale_conversation_message_cannot_be_added_after_cancellation(): void
    {
        $customer = $this->customer();

        $customization = CustomizationRequest::factory()->create([
            'user_id' => $customer->id,
            'status' => CustomizationRequestStatus::UnderReview,
        ]);

        $stale = $customization->fresh();

        $this->forceStatus(
            $customization,
            CustomizationRequestStatus::Cancelled
        );

        $request = $this->requestFor($customer, [
            'message' => 'Stale conversation message.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomizationConversationController::class)
                ->store($request, $stale)
        );

        $this->assertNoMessage(
            $customization,
            'Stale conversation message.'
        );

        $this->assertStatusIs(
            $customization,
            CustomizationRequestStatus::Cancelled
        );
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function requestFor(User $user, array $data): Request
    {
        $this->actingAs($user);

        $request = Request::create(
            '/stale-customization-transition',
            'POST',
            $data
        );

        $request->setUserResolver(
            fn (): User => $user
        );

        return $request;
    }

    private function forceStatus(
        CustomizationRequest $customization,
        CustomizationRequestStatus $status
    ): void {
        CustomizationRequest::query()
            ->whereKey($customization->id)
            ->update([
                'status' => $status->value,
            ]);
    }

    private function assertStatusIs(
        CustomizationRequest $customization,
        CustomizationRequestStatus $status
    ): void {
        $this->assertSame(
            $status,
            $customization->fresh()->status
        );
    }

    private function assertNoMessage(
        CustomizationRequest $customization,
        string $message
    ): void {
        $this->assertDatabaseMissing(
            'customization_messages',
            [
                'customization_request_id' => $customization->id,
                'message' => $message,
            ]
        );
    }

    private function assertHttpStatus(
        int $expectedStatus,
        callable $callback
    ): void {
        try {
            $callback();

            $this->fail(
                "Expected HTTP status {$expectedStatus}, but no exception was thrown."
            );
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(
                $expectedStatus,
                $exception->getStatusCode()
            );
        }
    }
}
