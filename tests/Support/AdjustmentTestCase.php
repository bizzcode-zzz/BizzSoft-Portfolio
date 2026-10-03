<?php

namespace Tests\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\ProductOwnership;
use App\Models\ProductRelease;
use App\Models\User;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Paddle\SDK\Entities\Event\EventTypeName;
use Paddle\SDK\Notifications\Entities\Adjustment;
use Paddle\SDK\Notifications\Entities\Transaction;
use Paddle\SDK\Notifications\Events\AdjustmentCreated;
use Paddle\SDK\Notifications\Events\AdjustmentUpdated;
use Paddle\SDK\Notifications\Events\TransactionCompleted;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class AdjustmentTestCase extends TestCase
{
    use UsesCheckoutDatabase;

    protected User $admin;

    protected User $customer;

    protected Product $product;

    protected Order $order;

    protected Payment $payment;

    protected ProductOwnership $ownership;

    protected ProductLicense $license;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        config(['paddle.api_key' => '', 'paddle.webhook_secret' => 'offline-signature-secret']);
        Schema::table('orders', fn (Blueprint $table) => $table->text('notes')->nullable());
        Schema::table('payments', fn (Blueprint $table) => $table->integer('verified_by')->nullable());
        foreach ([
            '2026_09_27_040040_create_payment_webhook_events_table.php',
            '2026_09_28_064207_create_product_releases_table.php',
            '2026_09_28_042810_create_product_ownerships_table.php',
            '2026_09_28_112347_add_starting_release_id_to_product_ownerships_table.php',
            '2026_09_29_035132_create_product_licenses_table.php',
            '2026_09_29_053122_add_revocation_fields_to_product_licenses_table.php',
            '2026_09_29_070445_create_product_license_activities_table.php',
        ] as $migration) {
            (require base_path('database/migrations/'.$migration))->up();
        }
        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->product = Product::create([
            'name' => 'Offline software', 'slug' => 'offline-software', 'price' => '100.00', 'status' => ProductStatus::Active,
        ]);
        [$this->order, $this->payment, $this->ownership, $this->license] = $this->purchase();
    }

    protected function purchase(): array
    {
        $number = ++$this->sequence;
        $order = Order::create([
            'order_number' => 'ORDER-ADJUST-'.$number, 'user_id' => $this->customer->id,
            'product_id' => $this->product->id, 'product_name_snapshot' => $this->product->name,
            'price_snapshot' => '100.00', 'status' => OrderStatus::Completed, 'ordered_at' => now(),
        ]);
        $payment = $order->payments()->create([
            'payment_number' => 'PAY-ADJUST-'.$number, 'user_id' => $this->customer->id,
            'amount' => '100.00', 'currency' => 'USD', 'provider' => 'paddle',
            'provider_payment_id' => 'txn_adjust_'.$number, 'status' => PaymentStatus::Pending,
        ]);
        app(PaddleWebhookProcessor::class)->process($this->completed($payment));
        $ownership = ProductOwnership::firstOrCreate(
            ['user_id' => $this->customer->id, 'product_id' => $this->product->id],
            ['order_id' => $order->id, 'granted_by' => $this->admin->id, 'granted_at' => now()],
        );
        $license = ProductLicense::create([
            'product_ownership_id' => $ownership->id, 'order_id' => $order->id,
            'license_key' => 'BIZZ-ADJUST-'.$number, 'status' => 'unactivated',
        ]);

        return [$order, $payment->fresh(), $ownership, $license];
    }

    protected function completed(Payment $payment, array $changes = [], ?string $eventId = null): TransactionCompleted
    {
        $data = [
            'id' => $payment->provider_payment_id, 'customer_id' => 'ctm_trusted',
            'status' => 'completed', 'origin' => 'api', 'currency_code' => 'USD',
            'collection_mode' => 'automatic', 'discount_id' => null,
            'custom_data' => [
                'bizzsoft_payment_id' => $payment->id, 'payment_number' => $payment->payment_number,
                'payable_type' => $payment->payable_type, 'payable_id' => $payment->payable_id, 'user_id' => $payment->user_id,
            ],
            'details' => [
                'tax_rates_used' => [], 'line_items' => [],
                'totals' => [
                    'subtotal' => '8333', 'discount' => '0', 'tax' => '1667', 'total' => '10000',
                    'credit' => '0', 'balance' => '0', 'grand_total' => '10000', 'currency_code' => 'USD',
                ],
            ],
            'created_at' => '2026-09-30T00:00:00Z', 'updated_at' => '2026-09-30T00:00:00Z',
        ];
        foreach ($changes as $key => $value) {
            data_set($data, $key, $value);
        }

        return TransactionCompleted::fromEvent(
            $eventId ?? 'evt_completed_'.$payment->id, new EventTypeName('transaction.completed'),
            new DateTimeImmutable('2026-09-30T00:00:00Z'), Transaction::from($data),
        );
    }

    protected function adjustmentData(array $changes = []): array
    {
        $data = [
            'id' => 'adj_first', 'transaction_id' => $this->payment->provider_payment_id,
            'customer_id' => 'ctm_trusted', 'currency_code' => 'USD',
            'action' => 'refund', 'status' => 'approved', 'type' => 'full', 'reason' => 'Offline refund',
            'items' => [[
                'id' => 'adjitm_one', 'item_id' => 'txnitm_one', 'type' => 'full', 'proration' => null,
                'totals' => ['subtotal' => '8333', 'tax' => '1667', 'total' => '10000'],
            ]],
            'totals' => [
                'subtotal' => '8333', 'tax' => '1667', 'total' => '10000', 'fee' => '0',
                'earnings' => '10000', 'currency_code' => 'USD',
            ],
            'created_at' => '2026-09-30T01:00:00.000000Z',
            'updated_at' => '2026-09-30T01:00:00.100000Z',
        ];
        foreach ($changes as $key => $value) {
            data_set($data, $key, $value);
        }

        return $data;
    }

    protected function event(array $changes = [], string $id = 'evt_adjust_first', bool $updated = false): AdjustmentCreated|AdjustmentUpdated
    {
        $class = $updated ? AdjustmentUpdated::class : AdjustmentCreated::class;

        return $class::fromEvent(
            $id, new EventTypeName($updated ? 'adjustment.updated' : 'adjustment.created'),
            new DateTimeImmutable('2026-09-30T02:00:00Z'), Adjustment::from($this->adjustmentData($changes)),
        );
    }

    protected function process(array $changes = [], string $id = 'evt_adjust_first', bool $updated = false): PaymentAdjustment
    {
        app(PaddleWebhookProcessor::class)->process($this->event($changes, $id, $updated));

        return PaymentAdjustment::where('provider_adjustment_id', $changes['id'] ?? 'adj_first')->firstOrFail();
    }

    protected function decide(PaymentAdjustment $ledger, string $action, array $changes = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.payment-adjustments.update', $ledger), array_replace([
            'action' => $action, 'reason' => 'Reviewed provider evidence with customer.',
            'last_event_id' => $ledger->last_event_id, 'history_count' => count($ledger->history),
        ], $changes));
    }

    protected function release(): ProductRelease
    {
        Storage::fake('local');
        Storage::disk('local')->put('offline.zip', 'offline package');

        return ProductRelease::create([
            'product_id' => $this->product->id, 'version' => '1.0', 'file_path' => 'offline.zip',
            'original_name' => 'offline.zip', 'file_size' => 15, 'sha256' => hash('sha256', 'offline package'),
            'status' => 'published', 'released_at' => now(),
        ]);
    }
}
