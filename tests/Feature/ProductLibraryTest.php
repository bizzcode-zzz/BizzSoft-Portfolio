<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\ProductOwnership;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_products_only_shows_releases_from_starting_entitlement_onward(): void
    {
        $customer = User::factory()->create();

        Role::findOrCreate('customer');
        $customer->assignRole('customer');

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'Product library entitlement test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $oldRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/'.$product->id.'/5.0.1.zip',
            'original_name' => 'bizzsoft-v5.0.1.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now()->subDays(2),
        ]);

        $startingRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.2',
            'file_path' => 'product-releases/'.$product->id.'/5.0.2.zip',
            'original_name' => 'bizzsoft-v5.0.2.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('b', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now()->subDay(),
        ]);

        $newerRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.3',
            'file_path' => 'product-releases/'.$product->id.'/5.0.3.zip',
            'original_name' => 'bizzsoft-v5.0.3.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('c', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'BS-LIBRARY-0001',
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::Completed,
            'ordered_at' => now(),
        ]);

        $ownership = ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'starting_release_id' => $startingRelease->id,
            'granted_at' => now(),
        ]);

        ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-TEST-LIBR-ARY1-0001',
            'status' => 'active',
            'production_domain' => 'company-a.test',
            'activated_at' => now(),
            'last_validated_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(route('customer.products.index'));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Customer/Products/Index')
                ->has('ownedProducts', 1)
                ->has('ownedProducts.0.licenses', 1)
                ->where(
                    'ownedProducts.0.licenses.0.license_key',
                    'BIZZ-TEST-LIBR-ARY1-0001'
                )
                ->where(
                    'ownedProducts.0.licenses.0.status',
                    'active'
                )
                ->where(
                    'ownedProducts.0.licenses.0.production_domain',
                    'company-a.test'
                )
                ->has('ownedProducts.0.licenses.0.activated_at')
                ->has('ownedProducts.0.product.releases', 2)
                ->where(
                    'ownedProducts.0.product.releases.0.id',
                    $newerRelease->id
                )
                ->where(
                    'ownedProducts.0.product.releases.0.version',
                    '5.0.3'
                )
                ->where(
                    'ownedProducts.0.product.releases.1.id',
                    $startingRelease->id
                )
                ->where(
                    'ownedProducts.0.product.releases.1.version',
                    '5.0.2'
                )
        );

        $this->assertNotSame(
            $oldRelease->id,
            $startingRelease->id
        );
    }
}
