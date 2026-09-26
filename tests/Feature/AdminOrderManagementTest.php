<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_view_all_orders(): void
    {
        $admin = $this->createAdmin();

        $firstCustomer = $this->createCustomer();
        $secondCustomer = $this->createCustomer();

        $product = $this->createProduct();

        $this->createOrder(
            customer: $firstCustomer,
            product: $product,
            orderNumber: 'BS-TEST-0001'
        );

        $this->createOrder(
            customer: $secondCustomer,
            product: $product,
            orderNumber: 'BS-TEST-0002'
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.orders.index'));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders', 2)
        );
    }

    public function test_admin_can_view_order_detail(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.orders.show', $order));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where(
                    'order.order_number',
                    $order->order_number
                )
                ->where(
                    'order.customer.id',
                    $customer->id
                )
                ->where(
                    'order.product_name',
                    'BizzSoft V5'
                )
                ->where(
                    'order.product_version',
                    '5'
                )
                ->where(
                    'order.price',
                    '20000.00'
                )
                ->where(
                    'order.status',
                    OrderStatus::Pending->value
                )
                ->has('statuses', 5)
        );
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = $this->createCustomer();

        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $this
            ->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();

        $this
            ->actingAs($customer)
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();

        $this
            ->actingAs($customer)
            ->patch(
                route('admin.orders.status.update', $order),
                [
                    'status' => OrderStatus::Processing->value,
                ]
            )
            ->assertForbidden();
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.orders.status.update', $order),
                [
                    'status' => OrderStatus::AwaitingPayment->value,
                ]
            );

        $response->assertRedirect(
            route('admin.orders.show', $order)
        );

        $order->refresh();

        $this->assertSame(
            OrderStatus::AwaitingPayment,
            $order->status
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::AwaitingPayment->value,
        ]);
    }

    public function test_invalid_order_status_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(
                route('admin.orders.status.update', $order),
                [
                    'status' => 'paid_but_not_real_status',
                ]
            );

        $response
            ->assertRedirect(
                route('admin.orders.show', $order)
            )
            ->assertSessionHasErrors('status');

        $order->refresh();

        $this->assertSame(
            OrderStatus::Pending,
            $order->status
        );
    }

    public function test_status_update_does_not_change_original_ordered_at(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-26 07:47:40')
        );

        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product
        );

        $originalOrderedAt = $order->ordered_at->copy();
        $originalUpdatedAt = $order->updated_at->copy();

        Carbon::setTestNow(
            Carbon::parse('2026-09-26 08:20:04')
        );

        $this
            ->actingAs($admin)
            ->patch(
                route('admin.orders.status.update', $order),
                [
                    'status' => OrderStatus::Processing->value,
                ]
            )
            ->assertRedirect(
                route('admin.orders.show', $order)
            );

        $order->refresh();

        $this->assertTrue(
            $order->ordered_at->equalTo($originalOrderedAt)
        );

        $this->assertTrue(
            $order->updated_at->greaterThan($originalUpdatedAt)
        );

        $this->assertSame(
            OrderStatus::Processing,
            $order->status
        );
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();

        Role::findOrCreate('admin');

        $admin->assignRole('admin');

        return $admin;
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
        string $orderNumber = 'BS-TEST-0001',
        OrderStatus $status = OrderStatus::Pending
    ): Order {
        return Order::create([
            'order_number' => $orderNumber,
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