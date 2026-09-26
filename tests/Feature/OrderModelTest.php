<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_persists_snapshots_casts_status_and_has_expected_relationships(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft product test record.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-TEST-0001',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::Pending,
            'notes' => 'Test order.',
            'ordered_at' => now(),
        ]);

        $order->refresh();

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

        $this->assertTrue(
            $order->user->is($user)
        );

        $this->assertTrue(
            $order->product->is($product)
        );

        $this->assertTrue(
            $user->orders()
                ->whereKey($order->id)
                ->exists()
        );

        $this->assertTrue(
            $product->orders()
                ->whereKey($order->id)
                ->exists()
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_number' => 'BS-TEST-0001',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_name_snapshot' => 'BizzSoft V5',
            'product_version_snapshot' => '5',
            'price_snapshot' => '20000.00',
            'status' => OrderStatus::Pending->value,
        ]);
    }
}