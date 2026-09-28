<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOwnership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOwnershipModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_ownership_relationships_work(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();

        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-OWN-0001'
        );

        $ownership = ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->assertTrue(
            $customer->productOwnerships()
                ->whereKey($ownership->id)
                ->exists()
        );

        $this->assertTrue(
            $admin->grantedProductOwnerships()
                ->whereKey($ownership->id)
                ->exists()
        );

        $this->assertTrue(
            $product->ownerships()
                ->whereKey($ownership->id)
                ->exists()
        );

        $this->assertTrue(
            $order->productOwnership()
                ->whereKey($ownership->id)
                ->exists()
        );

        $this->assertTrue(
            $ownership->user->is($customer)
        );

        $this->assertTrue(
            $ownership->product->is($product)
        );

        $this->assertTrue(
            $ownership->order->is($order)
        );

        $this->assertTrue(
            $ownership->grantor->is($admin)
        );
    }

    public function test_order_can_only_grant_one_product_ownership(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();

        $product = $this->createProduct();

        $order = $this->createOrder(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-OWN-0002'
        );

        ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);
    }

    public function test_customer_can_only_own_product_once(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();

        $product = $this->createProduct();

        $firstOrder = $this->createOrder(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-OWN-0003'
        );

        $secondOrder = $this->createOrder(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-OWN-0004'
        );

        ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $firstOrder->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $secondOrder->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);
    }

    private function createProduct(): Product
    {
        return Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft ownership test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);
    }

    private function createOrder(
        User $customer,
        Product $product,
        string $orderNumber
    ): Order {
        return Order::create([
            'order_number' => $orderNumber,
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::Processing,
            'ordered_at' => now(),
        ]);
    }
}
