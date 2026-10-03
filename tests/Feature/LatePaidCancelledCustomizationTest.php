<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Paddle\SDK\Entities\Event\EventTypeName;
use Paddle\SDK\Notifications\Entities\Transaction;
use Paddle\SDK\Notifications\Events\TransactionCompleted;
use Spatie\Permission\Models\Role;
use Tests\Support\UsesCheckoutDatabase;
use Tests\TestCase;

class LatePaidCancelledCustomizationTest extends TestCase
{
    use UsesCheckoutDatabase;

    private User $admin;

    private User $customer;

    private CustomizationRequest $customizationRequest;

    private CustomizationQuote $quote;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Schema::table(
            'payments',
            fn (Blueprint $table) =>
                $table->integer('verified_by')->nullable()
        );

        Schema::table(
            'customization_messages',
            fn (Blueprint $table) =>
                $table->text('message')->nullable()
        );

        Schema::create(
            'payment_webhook_events',
            function (Blueprint $table): void {
                $table->id();
                $table->string('provider');
                $table->string('event_id');
                $table->string('event_type');
                $table->string('provider_payment_id')->nullable();
                $table->dateTime('occurred_at')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->dateTime('failed_at')->nullable();
                $table->text('failure_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique([
                    'provider',
                    'event_id',
                ]);
            }
        );

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $this->customizationRequest =
            CustomizationRequest::create([
                'user_id' => $this->customer->id,
                'title' => 'Late paid cancelled customization',
                'description' => 'Test cancellation and payment recovery.',
                'status' => CustomizationRequestStatus::Cancelled,
            ]);

        $this->quote = $this->customizationRequest
            ->quote()
            ->create([
                'price' => '100.00',
                'scope' => 'Late payment recovery test scope.',
                'estimated_delivery' => now()
                    ->addWeek()
                    ->toDateString(),
            ]);

        $this->payment = $this->quote
            ->payments()
            ->create([
                'payment_number' => 'PAY-LATE-CUSTOM-001',
                'user_id' => $this->customer->id,
                'amount' => '100.00',
                'currency' => 'USD',
                'provider' => 'paddle',
                'provider_payment_id' =>
                    'txn_late_customization',
                'status' => PaymentStatus::Pending,
            ]);
    }

    public function test_unpaid_cancelled_customization_cannot_be_recovered(): void
    {
        $this->recover()
            ->assertSessionHasErrors('reason');

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );

        $this->assertDatabaseCount(
            'customization_messages',
            0
        );
    }

    public function test_late_completed_payment_is_verified_without_reopening_customization(): void
    {
        $event = $this->event();

        $this->assertTrue(
            app(PaddleWebhookProcessor::class)
                ->process($event)
        );

        $payment = $this->payment->fresh();

        $this->assertSame(
            PaymentStatus::Verified,
            $payment->status
        );

        $this->assertNotNull(
            $payment->verified_at
        );

        $this->assertSame(
            'txn_late_customization',
            $payment->provider_payment_id
        );

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );

        $this->assertFalse(
            app(PaddleWebhookProcessor::class)
                ->process($event)
        );

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );
    }

    public function test_admin_recovery_is_audited_and_only_moves_to_accepted(): void
    {
        app(PaddleWebhookProcessor::class)
            ->process($this->event());

        $this->recover()
            ->assertRedirect(
                route(
                    'admin.customizations.show',
                    $this->customizationRequest
                )
            );

        $this->assertSame(
            CustomizationRequestStatus::Accepted,
            $this->customizationRequest->fresh()->status
        );

        $message = $this->customizationRequest
            ->messages()
            ->sole();

        $this->assertSame(
            $this->admin->id,
            $message->user_id
        );

        $this->assertStringContainsString(
            '[Late-payment recovery',
            $message->message
        );

        $this->assertStringContainsString(
            'Admin #'.$this->admin->id,
            $message->message
        );

        $this->assertStringContainsString(
            'PAY-LATE-CUSTOM-001',
            $message->message
        );

        $this->assertStringContainsString(
            'Customer confirmed customization should continue.',
            $message->message
        );

        $this->recover()
            ->assertSessionHasErrors('reason');

        $this->assertSame(
            1,
            $this->customizationRequest
                ->messages()
                ->where(
                    'message',
                    'like',
                    '[Late-payment recovery%'
                )
                ->count()
        );

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.customizations.start-development',
                    $this->customizationRequest
                )
            )
            ->assertRedirect(
                route(
                    'admin.customizations.show',
                    $this->customizationRequest
                )
            );

        $this->assertSame(
            CustomizationRequestStatus::InProgress,
            $this->customizationRequest->fresh()->status
        );
    }

    public function test_partial_verified_payment_cannot_recover_cancelled_customization(): void
    {
        $this->payment->update([
            'amount' => '50.00',
        ]);

        app(PaddleWebhookProcessor::class)
            ->process(
                $this->event([
                    'details.totals.subtotal' => '5000',
                    'details.totals.total' => '5000',
                    'details.totals.grand_total' => '5000',
                ])
            );

        $this->assertSame(
            PaymentStatus::Verified,
            $this->payment->fresh()->status
        );

        $this->recover()
            ->assertSessionHasErrors('reason');

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );

        $this->assertDatabaseCount(
            'customization_messages',
            0
        );
    }

    public function test_customer_cannot_use_admin_payment_recovery(): void
    {
        app(PaddleWebhookProcessor::class)
            ->process($this->event());

        $this->actingAs($this->customer)
            ->post(
                route(
                    'admin.customizations.recover-payment',
                    $this->customizationRequest
                ),
                [
                    'reason' =>
                        'Try to reopen my own customization.',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );

        $this->assertDatabaseCount(
            'customization_messages',
            0
        );
    }

    public function test_admin_recovery_requires_reason(): void
    {
        app(PaddleWebhookProcessor::class)
            ->process($this->event());

        $this->actingAs($this->admin)
            ->post(
                route(
                    'admin.customizations.recover-payment',
                    $this->customizationRequest
                ),
                []
            )
            ->assertSessionHasErrors('reason');

        $this->assertSame(
            CustomizationRequestStatus::Cancelled,
            $this->customizationRequest->fresh()->status
        );

        $this->assertDatabaseCount(
            'customization_messages',
            0
        );
    }

    public function test_admin_show_does_not_flag_unpaid_cancelled_customization(): void
    {
        $this->actingAs($this->admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $this->customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) =>
                    $page
                        ->component(
                            'Admin/Customizations/Show'
                        )
                        ->where(
                            'paymentRecovery.needs_review',
                            false
                        )
                        ->where(
                            'paymentRecovery.verified_amount',
                            '0.00'
                        )
                        ->where(
                            'paymentRecovery.can_recover',
                            false
                        )
            );
    }

    public function test_admin_show_flags_fully_paid_cancelled_customization_for_recovery(): void
    {
        app(PaddleWebhookProcessor::class)
            ->process($this->event());

        $this->actingAs($this->admin)
            ->get(
                route(
                    'admin.customizations.show',
                    $this->customizationRequest
                )
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) =>
                    $page
                        ->component(
                            'Admin/Customizations/Show'
                        )
                        ->where(
                            'paymentRecovery.needs_review',
                            true
                        )
                        ->where(
                            'paymentRecovery.verified_amount',
                            '100.00'
                        )
                        ->where(
                            'paymentRecovery.can_recover',
                            true
                        )
            );
    }

    private function recover(): TestResponse
    {
        return $this->actingAs($this->admin)
            ->post(
                route(
                    'admin.customizations.recover-payment',
                    $this->customizationRequest
                ),
                [
                    'reason' =>
                        'Customer confirmed customization should continue.',
                ]
            );
    }

    private function event(
        array $changes = [],
        string $id = 'evt_late_customization_completed'
    ): TransactionCompleted {
        $data = [
            'id' => 'txn_late_customization',
            'status' => 'completed',
            'origin' => 'api',
            'currency_code' => 'USD',
            'collection_mode' => 'automatic',
            'discount_id' => null,

            'custom_data' => [
                'bizzsoft_payment_id' =>
                    $this->payment->id,

                'payment_number' =>
                    $this->payment->payment_number,

                'payable_type' =>
                    CustomizationQuote::class,

                'payable_id' =>
                    $this->quote->id,

                'user_id' =>
                    $this->customer->id,
            ],

            'details' => [
                'tax_rates_used' => [],
                'line_items' => [],

                'totals' => [
                    'subtotal' => '10000',
                    'discount' => '0',
                    'tax' => '0',
                    'total' => '10000',
                    'credit' => '0',
                    'balance' => '0',
                    'grand_total' => '10000',
                    'currency_code' => 'USD',
                    'credit_to_balance' => '0',
                    'grand_total_tax' => '0',
                ],
            ],

            'created_at' =>
                '2026-10-01T00:00:00Z',

            'updated_at' =>
                '2026-10-01T00:00:00Z',
        ];

        foreach ($changes as $field => $value) {
            data_set(
                $data,
                $field,
                $value
            );
        }

        return TransactionCompleted::fromEvent(
            $id,
            new EventTypeName(
                'transaction.completed'
            ),
            new DateTimeImmutable(
                '2026-10-01T01:00:00Z'
            ),
            Transaction::from($data)
        );
    }
}