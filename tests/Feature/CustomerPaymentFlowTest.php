<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_payment_page_for_awaiting_payment_order(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::AwaitingPayment
        );

        $response = $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Payments/Order')
                ->where('order.id', $order->id)
                ->where('order.order_number', $order->order_number)
                ->where('order.product_name', 'BizzSoft V5')
                ->where('order.price', '20000.00')
                ->where(
                    'order.status',
                    OrderStatus::AwaitingPayment->value
                )
                ->where('verified_amount', '0.00')
                ->where('remaining_amount', '20000.00')
                ->has('payments', 0)
        );
    }

    public function test_legacy_pending_order_can_still_open_payment_page(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Pending
        );

        $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertOk();
    }

    public function test_customer_cannot_open_another_customers_payment_page(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $owner,
            product: $product,
            status: OrderStatus::AwaitingPayment
        );

        $this
            ->actingAs($otherCustomer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertNotFound();
    }

    public function test_processing_order_cannot_open_payment_page(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Processing
        );

        $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertStatus(422);
    }

    public function test_completed_order_cannot_open_payment_page(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Completed
        );

        $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertStatus(422);
    }

    public function test_cancelled_order_cannot_open_payment_page(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Cancelled
        );

        $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertStatus(422);
    }

    public function test_fully_verified_order_payment_cannot_be_paid_again(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::AwaitingPayment
        );

        $order->payments()->create([
            'payment_number' => 'PAY-FULL-001',
            'user_id' => $customer->id,
            'amount' => '20000.00',
            'currency' => 'PHP',
            'provider' => 'card_gateway',
            'method' => 'card',
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order))
            ->assertStatus(422);
    }

    public function test_partial_verified_payment_reduces_remaining_amount(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::AwaitingPayment
        );

        $order->payments()->create([
            'payment_number' => 'PAY-PARTIAL-001',
            'user_id' => $customer->id,
            'amount' => '5000.00',
            'currency' => 'PHP',
            'provider' => 'manual',
            'method' => 'bank_transfer',
            'status' => PaymentStatus::Verified,
            'submitted_at' => now(),
            'verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(route('customer.orders.payment.show', $order));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Payments/Order')
                ->where('verified_amount', '5000.00')
                ->where('remaining_amount', '15000.00')
                ->has('payments', 1)
                ->where(
                    'payments.0.status',
                    PaymentStatus::Verified->value
                )
        );
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();

        Role::findOrCreate('customer');

        $customer->assignRole('customer');

        return $customer;
    }

    private function createProduct(): Product
    {
        return Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft product test record.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);
    }

    private function createOrder(
        User $customer,
        Product $product,
        OrderStatus $status
    ): Order {
        static $counter = 1;

        return Order::create([
            'order_number' => sprintf(
                'BS-PAY-TEST-%04d',
                $counter++
            ),
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => $status,
            'ordered_at' => now(),
        ]);
    }
}