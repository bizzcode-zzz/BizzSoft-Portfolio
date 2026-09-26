<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_order_an_active_product_with_snapshot_values(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct(
            status: ProductStatus::Active,
            price: 20000,
            version: '5'
        );

        $response = $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product));

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(
            route('customer.orders.show', $order)
        );

        $this->assertSame(
            $customer->id,
            $order->user_id
        );

        $this->assertSame(
            $product->id,
            $order->product_id
        );

        $this->assertSame(
            'BizzSoft V5',
            $order->product_name_snapshot
        );

        $this->assertSame(
            '5',
            $order->product_version_snapshot
        );

        $this->assertSame(
            '20000.00',
            $order->price_snapshot
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status
        );

        $this->assertNotEmpty(
            $order->order_number
        );

        $this->assertNotNull(
            $order->ordered_at
        );
    }

    public function test_customer_with_existing_non_cancelled_order_is_redirected_to_existing_order(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct();

        $existingOrder = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Pending
        );

        $response = $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product));

        $response->assertRedirect(
            route('customer.orders.show', $existingOrder)
        );

        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('orders', [
            'id' => $existingOrder->id,
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'status' => OrderStatus::Pending->value,
        ]);
    }

    public function test_customer_can_order_again_after_previous_order_is_cancelled(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct();

        $cancelledOrder = $this->createOrder(
            customer: $customer,
            product: $product,
            status: OrderStatus::Cancelled
        );

        $response = $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product));

        $newOrder = Order::query()
            ->whereKeyNot($cancelledOrder->id)
            ->firstOrFail();

        $response->assertRedirect(
            route('customer.orders.show', $newOrder)
        );

        $this->assertDatabaseCount('orders', 2);

        $this->assertSame(
            OrderStatus::Pending,
            $newOrder->status
        );
    }

    public function test_draft_product_cannot_be_ordered(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct(
            status: ProductStatus::Draft
        );

        $response = $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product));

        $response->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_product_cannot_be_ordered(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct(
            status: ProductStatus::Inactive
        );

        $response = $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product));

        $response->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_view_their_own_order(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $response = $this
            ->actingAs($customer)
            ->get(route('customer.orders.show', $order));

        $response->assertOk();
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();

        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $owner,
            product: $product
        );

        $response = $this
            ->actingAs($otherCustomer)
            ->get(route('customer.orders.show', $order));

        $response->assertNotFound();
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();

        Role::findOrCreate('customer');

        $customer->assignRole('customer');

        return $customer;
    }

    private function createProduct(
        ProductStatus $status = ProductStatus::Active,
        float|int $price = 20000,
        string $version = '5'
    ): Product {
        return Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft product test record.',
            'price' => $price,
            'status' => $status,
            'version' => $version,
        ]);
    }

    private function createOrder(
        User $customer,
        Product $product,
        OrderStatus $status = OrderStatus::Pending
    ): Order {
        static $counter = 1;

        return Order::create([
            'order_number' => sprintf(
                'BS-TEST-%04d',
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