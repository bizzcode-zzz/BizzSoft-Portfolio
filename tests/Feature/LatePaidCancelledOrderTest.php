<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Paddle\SDK\Entities\Event\EventTypeName;
use Paddle\SDK\Notifications\Entities\Transaction;
use Paddle\SDK\Notifications\Events\TransactionCompleted;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Support\UsesCheckoutDatabase;
use Tests\TestCase;

class LatePaidCancelledOrderTest extends TestCase
{
    use UsesCheckoutDatabase;

    private User $admin;

    private User $customer;

    private Order $order;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Extend only this test's disposable in-memory fixture; no migrations.
        Schema::table('orders', fn (Blueprint $table) => $table->text('notes')->nullable());
        Schema::table('payments', fn (Blueprint $table) => $table->integer('verified_by')->nullable());
        Schema::create('payment_webhook_events', function (Blueprint $table) {
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
            $table->unique(['provider', 'event_id']);
        });
        foreach (['product_ownerships', 'product_licenses'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }

        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->order = Order::create([
            'order_number' => 'ORDER-LATE-001', 'user_id' => $this->customer->id, 'product_id' => 1,
            'product_name_snapshot' => 'Test software', 'price_snapshot' => '100.00',
            'status' => OrderStatus::Cancelled, 'ordered_at' => now(), 'notes' => 'Original cancellation note.',
        ]);
        $this->payment = $this->order->payments()->create([
            'payment_number' => 'PAY-LATE-001', 'user_id' => $this->customer->id,
            'amount' => '100.00', 'currency' => 'USD', 'provider' => 'paddle',
            'provider_payment_id' => 'txn_late', 'status' => PaymentStatus::Pending,
        ]);
    }

    public function test_unpaid_cancelled_order_remains_cancelled_and_cannot_be_recovered(): void
    {
        $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('order.payment_review.needs_review', false)
            ->where('order.payment_review.can_recover', false));
        $this->recover()->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertSame('Original cancellation note.', $this->order->fresh()->notes);
        $this->assertNoGrants();
    }

    public function test_valid_late_payment_is_verified_and_surfaced_without_reopening_or_granting_access(): void
    {
        $event = $this->event();
        $this->assertTrue(app(PaddleWebhookProcessor::class)->process($event));
        $payment = $this->payment->fresh();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame('100.00', $payment->amount);
        $this->assertSame('txn_late', $payment->provider_payment_id);
        $this->assertSame($event->occurredAt->format(DATE_ATOM), $payment->verified_at->format(DATE_ATOM));
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);

        $this->actingAs($this->admin)->get(route('admin.orders.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('orders.data.0.payment_review.needs_review', true));
        $this->get(route('admin.orders.show', $this->order))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('order.payment_review.can_recover', true)
            ->where('order.payment_review.verified_amount', '100.00'));
        $this->assertNoGrants();
    }

    public function test_admin_recovery_is_audited_and_only_moves_to_processing_with_replays_idempotent(): void
    {
        $processor = app(PaddleWebhookProcessor::class);
        $event = $this->event();
        $processor->process($event);
        $this->assertFalse($processor->process($event));
        $this->recover()->assertRedirect(route('admin.orders.show', $this->order));
        $order = $this->order->fresh();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertStringContainsString('Original cancellation note.', $order->notes);
        $this->assertStringContainsString('Admin #'.$this->admin->id, $order->notes);
        $this->assertStringContainsString('PAY-LATE-001', $order->notes);
        $this->assertStringContainsString('Customer confirmed fulfillment is wanted.', $order->notes);
        $this->assertSame(1, substr_count($order->notes, '[Late-payment recovery'));
        $this->assertNoGrants();

        $this->recover()->assertSessionHasErrors('reason');
        $this->assertFalse($processor->process($event));
        $this->assertTrue($processor->process($this->event(id: 'evt_late_second_delivery')));
        $this->assertSame($order->notes, $this->order->fresh()->notes);
        $this->assertSame(OrderStatus::Processing, $this->order->fresh()->status);
        $this->assertSame(1, Payment::count());
        $this->assertNoGrants();
    }

    public function test_partial_verified_payment_is_visible_but_cannot_recover_order(): void
    {
        $this->payment->update(['amount' => '50.00']);
        app(PaddleWebhookProcessor::class)->process($this->event(['details.totals.total' => '5000']));
        $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('order.payment_review.needs_review', true)
            ->where('order.payment_review.can_recover', false)
            ->where('order.payment_review.verified_amount', '50.00'));
        $this->recover()->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    #[DataProvider('invalidTransactions')]
    public function test_invalid_webhook_cannot_verify_or_recover_order(string $field, string $value): void
    {
        try {
            app(PaddleWebhookProcessor::class)->process($this->event([$field => $value]));
            $this->fail('Invalid payment was accepted.');
        } catch (InvalidArgumentException|RuntimeException) {
            $this->assertSame(PaymentStatus::Pending, $this->payment->fresh()->status);
            $this->assertNotNull(PaymentWebhookEvent::firstOrFail()->failed_at);
        }
        $this->recover()->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    public static function invalidTransactions(): array
    {
        return [
            ['details.totals.total', '10001'], ['currency_code', 'EUR'], ['id', 'txn_unmatched'],
            ['custom_data.bizzsoft_payment_id', '999'], ['custom_data.payment_number', 'wrong'],
            ['custom_data.payable_type', 'wrong'], ['custom_data.payable_id', '999'],
            ['custom_data.user_id', '999'], ['discount_id', 'dsc_unexpected'],
        ];
    }

    public function test_customer_cannot_use_admin_recovery(): void
    {
        app(PaddleWebhookProcessor::class)->process($this->event());
        $this->actingAs($this->customer)->post(route('admin.orders.recover-payment', $this->order),
            ['reason' => 'Try to reopen'])->assertForbidden();
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    public function test_recovery_requires_a_reason_and_cannot_bypass_normal_completion_transition(): void
    {
        app(PaddleWebhookProcessor::class)->process($this->event());
        $this->actingAs($this->admin)->post(route('admin.orders.recover-payment', $this->order), [])
            ->assertSessionHasErrors('reason');
        foreach ([OrderStatus::Processing, OrderStatus::Completed] as $status) {
            $this->patch(route('admin.orders.status.update', $this->order), ['status' => $status->value])
                ->assertSessionHasErrors('status');
        }
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    public function test_recovery_rejects_a_conflicting_replacement_order(): void
    {
        app(PaddleWebhookProcessor::class)->process($this->event());
        $replacement = $this->order->replicate();
        $replacement->order_number = 'ORDER-REPLACEMENT';
        $replacement->status = OrderStatus::AwaitingPayment;
        $replacement->save();
        $this->recover()->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    public function test_existing_normal_successful_payment_still_advances_to_processing(): void
    {
        $this->order->update(['status' => OrderStatus::AwaitingPayment]);
        app(PaddleWebhookProcessor::class)->process($this->event());
        $this->assertSame(OrderStatus::Processing, $this->order->fresh()->status);
        $this->assertNoGrants();
    }

    private function recover(): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.orders.recover-payment', $this->order), [
            'reason' => 'Customer confirmed fulfillment is wanted.',
        ]);
    }

    private function assertNoGrants(): void
    {
        $this->assertSame(0, DB::table('product_ownerships')->count());
        $this->assertSame(0, DB::table('product_licenses')->count());
    }

    private function event(array $changes = [], string $id = 'evt_late_completed'): TransactionCompleted
    {
        $data = [
            'id' => 'txn_late', 'status' => 'completed', 'origin' => 'api', 'currency_code' => 'USD',
            'collection_mode' => 'automatic', 'discount_id' => null,
            'custom_data' => [
                'bizzsoft_payment_id' => $this->payment->id, 'payment_number' => $this->payment->payment_number,
                'payable_type' => Order::class, 'payable_id' => $this->order->id, 'user_id' => $this->customer->id,
            ],
            'details' => [
                'tax_rates_used' => [], 'line_items' => [],
                'totals' => [
                    'subtotal' => '10000', 'discount' => '0', 'tax' => '0', 'total' => '10000',
                    'credit' => '0', 'balance' => '0', 'grand_total' => '10000', 'currency_code' => 'USD',
                ],
            ],
            'created_at' => '2026-09-30T00:00:00Z', 'updated_at' => '2026-09-30T00:00:00Z',
        ];
        foreach ($changes as $field => $value) {
            data_set($data, $field, $value);
        }

        return TransactionCompleted::fromEvent(
            $id, new EventTypeName('transaction.completed'), new DateTimeImmutable('2026-09-30T01:00:00Z'),
            Transaction::from($data),
        );
    }
}
