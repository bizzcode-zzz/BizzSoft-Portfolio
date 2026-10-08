<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\PaymentController;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Payments\CheckoutReservations;
use App\Payments\PaymentNumberGenerator;
use App\Payments\Providers\Paddle\PaddleCheckoutRecovery;
use App\Payments\Providers\Paddle\PaddleGateway;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckoutReservationTest extends TestCase
{
    private array $strategies;

    private array $requests = [];

    private $respond;

    private Order $order;

    private User $user;

    private Request $request;

    private ReservationRecordingGrammar $grammar;

    protected function setUp(): void
    {
        parent::setUp();

        // A separate in-memory connection, with no migrations or access to application records.
        config([
            'database.default' => 'checkout_offline',
            'database.connections.checkout_offline' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
            ],
            'paddle.api_key' => 'offline-test-key', 'paddle.environment' => 'sandbox',
            'session.driver' => 'array', 'logging.default' => 'null',
        ]);
        DB::purge('checkout_offline');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->string('product_name_snapshot');
            $table->string('product_version_snapshot')->nullable();
            $table->decimal('price_snapshot', 12, 2);
            $table->string('status');
            $table->dateTime('ordered_at');
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->morphs('payable');
            $table->integer('user_id');
            $table->decimal('amount', 18, 2);
            $table->string('currency');
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });
        DB::table('users')->insert(['id' => 1]);
        $this->user = User::findOrFail(1);
        $this->order = Order::create([
            'order_number' => 'ORDER-OFFLINE', 'user_id' => 1, 'product_id' => 1,
            'product_name_snapshot' => 'Test software', 'price_snapshot' => '100.00',
            'status' => OrderStatus::AwaitingPayment, 'ordered_at' => now(),
        ]);
        $this->grammar = new ReservationRecordingGrammar(DB::connection());
        DB::connection()->setQueryGrammar($this->grammar);

        $this->request = Request::create('/offline-checkout', 'POST', ['accepted_terms' => true]);
        $this->request->headers->set('X-Inertia', 'true');
        $this->request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $this->request);
        Facade::clearResolvedInstance('request');
        $this->request->setUserResolver(fn () => $this->user);

        $this->strategies = [...HttpAsyncClientDiscovery::getStrategies()];
        $transport = Mockery::mock(HttpAsyncClient::class);
        $transport->shouldReceive('sendAsyncRequest')->andReturnUsing(function ($request) {
            $this->assertSame(0, DB::transactionLevel(), 'No provider request may run inside a DB transaction.');
            $this->requests[] = $request->getMethod().' '.$request->getUri()->getPath();
            if ($this->respond) {
                return ($this->respond)($request);
            }

            return $this->providerResponse();
        });
        ReservationOfflineDiscovery::$client = $transport;
        HttpAsyncClientDiscovery::prependStrategy(ReservationOfflineDiscovery::class);
    }

    protected function tearDown(): void
    {
        HttpAsyncClientDiscovery::setStrategies($this->strategies);
        ReservationOfflineDiscovery::$client = null;
        DB::purge('checkout_offline');
        parent::tearDown();
    }

    public function test_order_checkout_requires_legal_consent(): void
    {
        $this->request->replace([]);

        try {
            $this->checkout();
            $this->fail('Expected checkout to require legal consent.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'accepted_terms',
                $exception->errors()
            );
        }

        $this->assertSame(0, Payment::count());
        $this->assertSame([], $this->requests);
    }

    public function test_reservation_rejects_an_ambient_database_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Checkout must start outside a database transaction.');

        DB::transaction(fn () => app(CheckoutReservations::class)->reserve(
            $this->order, 1, 'paddle', new PaymentNumberGenerator,
        ));
    }

    public function test_normal_checkout_and_repeated_request_reuse_one_payment_and_post(): void
    {
        $first = $this->checkout();
        $second = $this->checkout();
        $this->assertSame(409, $first->getStatusCode());
        $this->assertSame($first->headers->get('X-Inertia-Location'), $second->headers->get('X-Inertia-Location'));
        $this->assertSame(['POST /transactions'], $this->requests);
        $this->assertSame(1, Payment::count());
        $this->assertSame('ready', data_get(Payment::first()->metadata, 'checkout_reservation.state'));
        $this->assertContains(['orders', 1], $this->grammar->locks);
    }

    public function test_second_request_sees_committed_reservation_while_first_provider_call_is_in_progress(): void
    {
        $this->respond = function () {
            $payment = Payment::firstOrFail();
            $this->assertSame('creating', data_get($payment->metadata, 'checkout_reservation.state'));
            $this->assertHttpFailure(409, fn () => $this->checkout());
            $this->assertSame(1, Payment::count());

            return $this->providerResponse();
        };
        $this->checkout();
        $this->assertSame(['POST /transactions'], $this->requests);
    }

    public function test_ambiguous_network_failure_sends_exactly_one_post_and_never_expires_into_retry(): void
    {
        $this->respond = fn ($request) => new RejectedPromise(new NetworkException('Lost response', $request));
        $this->assertHttpFailure(502, fn () => $this->checkout());
        $payment = Payment::firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('outcome_unknown', data_get($payment->metadata, 'checkout_reservation.state'));
        $this->travel(30)->days();
        $this->assertHttpFailure(409, fn () => $this->checkout());
        $this->assertSame(['POST /transactions'], $this->requests);
        $this->assertSame(1, Payment::count());
    }

    #[DataProvider('ambiguousResponses')]
    public function test_server_errors_and_malformed_responses_are_not_retried(int $status, string $body): void
    {
        $this->respond = fn () => new FulfilledPromise(new Response($status, [], $body));
        $this->assertHttpFailure(502, fn () => $this->checkout());
        $this->assertHttpFailure(409, fn () => $this->checkout());
        $this->assertSame(['POST /transactions'], $this->requests);
        $this->assertSame('outcome_unknown', data_get(Payment::first()->metadata, 'checkout_reservation.state'));
    }

    public function test_production_ambiguous_network_failure_sends_exactly_one_post(): void
    {
        config(['paddle.environment' => 'production']);

        $this->respond = fn ($request) => new RejectedPromise(
            new NetworkException('Lost response', $request)
        );

        $this->assertHttpFailure(502, fn () => $this->checkout());

        $this->assertSame(['POST /transactions'], $this->requests);
        $this->assertSame(
            'outcome_unknown',
            data_get(Payment::firstOrFail()->metadata, 'checkout_reservation.state')
        );
    }

    public static function ambiguousResponses(): array
    {
        return [
            'server error' => [503, '{"error":{"type":"api_error","code":"internal_error","detail":"Unavailable","documentation_url":"https://example.test/error"}}'],
            'malformed success' => [201, 'not-json'],
        ];
    }

    public function test_explicit_paddle_request_rejection_allows_a_new_reserved_attempt(): void
    {
        $this->respond = fn () => new FulfilledPromise(
            new Response(
                400,
                [],
                '{"error":{"type":"request_error","code":"product_tax_category_not_approved","detail":"tax category not approved","documentation_url":"https://example.test/error"}}'
            )
        );

        $this->assertHttpFailure(502, fn () => $this->checkout());

        $payment = Payment::firstOrFail();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(
            'confirmed_failed',
            data_get($payment->metadata, 'checkout_reservation.state')
        );
        $this->assertNull($payment->provider_payment_id);
        $this->assertSame(['POST /transactions'], $this->requests);

        $this->respond = null;

        $retry = $this->checkout();

        $this->assertSame(409, $retry->getStatusCode());
        $this->assertSame(2, Payment::count());
        $this->assertSame(
            1,
            Payment::where('status', PaymentStatus::Failed)->count()
        );
        $this->assertSame(
            1,
            Payment::where('status', PaymentStatus::Pending)->count()
        );
        $this->assertSame(
            ['POST /transactions', 'POST /transactions'],
            $this->requests
        );
    }
    public function test_proven_pre_dispatch_failure_allows_a_new_reserved_attempt(): void
    {
        config(['paddle.api_key' => '']);
        $this->assertHttpFailure(502, fn () => $this->checkout());
        $this->assertSame([], $this->requests);
        $this->assertSame(PaymentStatus::Failed, Payment::first()->status);
        $this->assertSame('confirmed_failed', data_get(Payment::first()->metadata, 'checkout_reservation.state'));

        config(['paddle.api_key' => 'offline-test-key']);
        $this->checkout();
        $this->assertSame(2, Payment::count());
        $this->assertSame(1, Payment::where('status', PaymentStatus::Pending)->count());
        $this->assertSame(['POST /transactions'], $this->requests);
    }

    public function test_missing_url_retains_provider_id_and_can_be_recovered_using_get_only(): void
    {
        $this->respond = fn () => $this->providerResponse(url: null);
        $this->assertHttpFailure(409, fn () => $this->checkout());
        $payment = Payment::firstOrFail();
        $this->assertSame('txn_offline', $payment->provider_payment_id);
        $this->assertSame('outcome_unknown', data_get($payment->metadata, 'checkout_reservation.state'));
        $this->assertHttpFailure(409, fn () => $this->checkout());

        $this->respond = null;
        $recovered = app(PaddleCheckoutRecovery::class)->recover($payment);
        $this->assertSame('ready', data_get($recovered->metadata, 'checkout_reservation.state'));
        $this->checkout();
        $this->assertSame(['POST /transactions', 'GET /transactions/txn_offline'], $this->requests);
    }

    public function test_unknown_id_reconciliation_binds_only_a_strictly_matching_existing_transaction(): void
    {
        $this->respond = fn ($request) => new RejectedPromise(new NetworkException('Lost response', $request));
        $this->assertHttpFailure(502, fn () => $this->checkout());
        $this->respond = null;
        $payment = app(PaddleCheckoutRecovery::class)->recover(Payment::firstOrFail(), 'txn_offline');
        $this->assertSame('txn_offline', $payment->provider_payment_id);
        $this->assertSame(['POST /transactions', 'GET /transactions/txn_offline'], $this->requests);
    }

    #[DataProvider('mismatches')]
    public function test_recovery_rejects_amount_currency_and_identity_mismatches(string $field, string $value): void
    {
        [$payment] = app(CheckoutReservations::class)->reserve($this->order, 1, 'paddle', new PaymentNumberGenerator);
        $this->respond = function () use ($field, $value) {
            $data = $this->providerData();
            data_set($data, $field, $value);

            return new FulfilledPromise(new Response(200, [], json_encode(['data' => $data], JSON_THROW_ON_ERROR)));
        };
        try {
            app(PaddleCheckoutRecovery::class)->recover($payment, 'txn_offline');
            $this->fail('Mismatched transaction was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertNull($payment->fresh()->provider_payment_id);
            $this->assertSame('creating', data_get($payment->fresh()->metadata, 'checkout_reservation.state'));
        }
    }

    public static function mismatches(): array
    {
        return [
            ['details.totals.total', '10001'], ['currency_code', 'EUR'],
            ['custom_data.bizzsoft_payment_id', '999'], ['custom_data.payment_number', 'wrong'],
            ['custom_data.payable_type', 'wrong'], ['custom_data.payable_id', '999'],
            ['custom_data.user_id', '999'], ['discount_id', 'dsc_unexpected'],
        ];
    }

    public function test_finalization_preserves_status_and_metadata_written_while_provider_is_in_flight(): void
    {
        $this->respond = function () {
            $payment = Payment::firstOrFail();
            $metadata = $payment->metadata;
            $metadata['paddle_webhook'] = ['event_id' => 'evt_offline'];
            $payment->update(['status' => PaymentStatus::Verified, 'verified_at' => now(), 'metadata' => $metadata]);

            return $this->providerResponse();
        };
        $this->checkout();
        $payment = Payment::firstOrFail();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertSame('evt_offline', data_get($payment->metadata, 'paddle_webhook.event_id'));
    }

    public function test_recovery_rechecks_reservation_token_after_get(): void
    {
        [$payment] = app(CheckoutReservations::class)->reserve($this->order, 1, 'paddle', new PaymentNumberGenerator);
        $this->respond = function () {
            $fresh = Payment::firstOrFail();
            $metadata = $fresh->metadata;
            $metadata['checkout_reservation']['token'] = 'changed';
            $fresh->update(['metadata' => $metadata]);

            return $this->providerResponse();
        };
        try {
            app(PaddleCheckoutRecovery::class)->recover($payment, 'txn_offline');
            $this->fail('A changed reservation was overwritten.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('reservation changed', $exception->getMessage());
            $this->assertNull($payment->fresh()->provider_payment_id);
        }
    }

    public function test_persistence_failure_after_provider_success_leaves_reservation_blocked(): void
    {
        Payment::updating(function (Payment $payment) {
            if ($payment->isDirty('provider_payment_id')) {
                throw new RuntimeException('Simulated database write failure');
            }
        });
        try {
            $this->checkout();
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database write failure', $exception->getMessage());
        }
        $this->assertSame('creating', data_get(Payment::first()->metadata, 'checkout_reservation.state'));
        $this->assertHttpFailure(409, fn () => $this->checkout());
        $this->assertSame(['POST /transactions'], $this->requests);
    }

    public function test_duplicate_order_creation_uses_customer_lock_and_reuses_order(): void
    {
        $product = (new Product)->forceFill([
            'id' => 2, 'name' => 'Another product', 'version' => '1', 'price' => '100.00', 'status' => ProductStatus::Active,
        ]);
        $controller = new OrderController;
        $first = $controller->store($this->request, $product);
        $second = $controller->store($this->request, $product);
        $this->assertSame($first->getTargetUrl(), $second->getTargetUrl());
        $this->assertSame(1, Order::where('product_id', 2)->count());
        $this->assertContains(['users', 1], $this->grammar->locks);
        $this->assertSame([], $this->requests);
    }

    private function checkout(): \Symfony\Component\HttpFoundation\Response
    {
        return (new PaymentController)->createOrderCheckout(
            $this->request, $this->order, app(PaddleGateway::class), new PaymentNumberGenerator, new CheckoutReservations,
        );
    }

    private function assertHttpFailure(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected HTTP failure '.$status);
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    private function providerResponse(?string $url = 'https://checkout.example.test/offline'): FulfilledPromise
    {
        $data = $this->providerData();
        $data['checkout'] = ['url' => $url];

        return new FulfilledPromise(new Response(201, [], json_encode(['data' => $data], JSON_THROW_ON_ERROR)));
    }

    private function providerData(): array
    {
        $payment = Payment::latest('id')->firstOrFail();

        return [
            'id' => 'txn_offline', 'status' => 'draft', 'origin' => 'api', 'currency_code' => 'USD',
            'collection_mode' => 'automatic', 'discount_id' => null,
            'custom_data' => [
                'bizzsoft_payment_id' => $payment->id, 'payment_number' => $payment->payment_number,
                'payable_type' => $payment->payable_type, 'payable_id' => $payment->payable_id, 'user_id' => $payment->user_id,
            ],
            'details' => [
                'tax_rates_used' => [], 'line_items' => [],
                'totals' => [
                    'subtotal' => '10000', 'discount' => '0', 'tax' => '0', 'total' => '10000',
                    'credit' => '0', 'balance' => '10000', 'grand_total' => '10000', 'currency_code' => 'USD',
                    'credit_to_balance' => '0', 'grand_total_tax' => '0',
                ],
            ],
            'checkout' => ['url' => 'https://checkout.example.test/offline'],
            'created_at' => '2026-09-30T00:00:00Z', 'updated_at' => '2026-09-30T00:00:00Z',
        ];
    }
}

final class ReservationOfflineDiscovery implements DiscoveryStrategy
{
    public static ?HttpAsyncClient $client = null;

    public static function getCandidates($type): array
    {
        return $type === HttpAsyncClient::class ? [['class' => static fn () => self::$client]] : [];
    }
}

final class ReservationRecordingGrammar extends SQLiteGrammar
{
    public array $locks = [];

    public function compileSelect(Builder $query)
    {
        if ($query->lock === true) {
            $this->locks[] = [$query->from, $query->getConnection()->transactionLevel()];
        }

        return parent::compileSelect($query);
    }
}
