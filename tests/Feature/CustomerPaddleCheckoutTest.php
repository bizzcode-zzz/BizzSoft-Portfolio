<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPaddleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_paddle_checkout_for_own_awaiting_payment_order(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $gateway = Mockery::mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('provider')
            ->andReturn('paddle');

        $gateway
            ->shouldReceive('createCheckout')
            ->once()
            ->withArgs(function ($payment, $description) use ($order) {
                return $payment->amount === '349.00'
                    && $payment->currency === 'USD'
                    && $payment->status === PaymentStatus::Pending
                    && str_contains(
                        $description,
                        $order->order_number
                    );
            })
            ->andReturn(
                new CheckoutSession(
                    provider: 'paddle',
                    providerPaymentId: 'txn_test_001',
                    checkoutUrl: 'https://sandbox-checkout.example.test/txn_test_001',
                    metadata: [
                        'currency' => 'USD',
                        'tax_category' => 'standard',
                    ],
                )
            );

        $this->app->instance(
            PaymentGateway::class,
            $gateway
        );

        $response = $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customer.orders.payment.checkout',
                    $order
                )
            );

        $response
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                'https://sandbox-checkout.example.test/txn_test_001'
            );

        $this->assertDatabaseHas('payments', [
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => '349.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'provider_payment_id' => 'txn_test_001',
            'status' => PaymentStatus::Pending->value,
        ]);

        $payment = $order
            ->payments()
            ->firstOrFail();

        $this->assertSame(
            'https://sandbox-checkout.example.test/txn_test_001',
            $payment->metadata['checkout_url']
        );
    }

    public function test_existing_pending_paddle_checkout_is_reused(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $payment = $order->payments()->create([
            'payment_number' => 'PAY-REUSE-001',
            'user_id' => $customer->id,
            'amount' => '349.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'status' => PaymentStatus::Pending,
            'metadata' => [
                'checkout_url' =>
                    'https://sandbox-checkout.example.test/reuse',
            ],
        ]);

        $gateway = Mockery::mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('provider')
            ->andReturn('paddle');

        $gateway
            ->shouldNotReceive('createCheckout');

        $this->app->instance(
            PaymentGateway::class,
            $gateway
        );

        $response = $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customer.orders.payment.checkout',
                    $order
                )
            );

        $response
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                'https://sandbox-checkout.example.test/reuse'
            );

        $this->assertSame(
            1,
            $order->payments()->count()
        );

        $this->assertTrue(
            $order->payments()->first()->is($payment)
        );
    }

    public function test_failed_gateway_checkout_marks_local_payment_failed(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer);

        $gateway = Mockery::mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('provider')
            ->andReturn('paddle');

        $gateway
            ->shouldReceive('createCheckout')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Sandbox gateway failure.'
                )
            );

        $this->app->instance(
            PaymentGateway::class,
            $gateway
        );

        $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customer.orders.payment.checkout',
                    $order
                )
            )
            ->assertStatus(502);

        $payment = $order
            ->payments()
            ->firstOrFail();

        $this->assertSame(
            PaymentStatus::Failed,
            $payment->status
        );

        $this->assertSame(
            'Payment checkout could not be created.',
            $payment->notes
        );
    }

    public function test_customer_cannot_create_checkout_for_another_customers_order(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $order = $this->createOrder($owner);

        $gateway = Mockery::mock(PaymentGateway::class);

        $gateway
            ->shouldNotReceive('createCheckout');

        $this->app->instance(
            PaymentGateway::class,
            $gateway
        );

        $this
            ->actingAs($otherCustomer)
            ->post(
                route(
                    'customer.orders.payment.checkout',
                    $order
                )
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    private function createCustomer(): User
    {
        Role::findOrCreate('customer');

        $customer = User::factory()->create();

        $customer->assignRole('customer');

        return $customer;
    }

    private function createOrder(
        User $customer
    ): Order {
        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5-'.str()->random(8),
            'short_description' =>
                'Business management software.',
            'description' =>
                'BizzSoft product test record.',
            'price' => 349,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        return Order::create([
            'order_number' =>
                'BS-CHECKOUT-'.str()->upper(
                    str()->random(8)
                ),
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::AwaitingPayment,
            'ordered_at' => now(),
        ]);
    }
}
