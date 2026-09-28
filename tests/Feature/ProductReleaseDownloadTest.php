<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOwnership;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductReleaseDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_published_product_release(): void
    {
        Storage::fake('local');

        $customer = $this->createCustomer();
        $product = $this->createProduct(
            name: 'BizzSoft V5',
            slug: 'bizzsoft-v5'
        );

        $this->grantOwnership(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-DOWNLOAD-0001'
        );

        $release = $this->createRelease(
            product: $product,
            status: ProductReleaseStatus::Published
        );

        Storage::disk('local')->put(
            $release->file_path,
            'test release contents'
        );

        $response = $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.products.releases.download',
                    [$product, $release]
                )
            );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'application/zip'
        );

        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get(
                'content-disposition'
            )
        );

        $this->assertStringContainsString(
            $release->original_name,
            (string) $response->headers->get(
                'content-disposition'
            )
        );
    }

    public function test_non_owner_cannot_download_product_release(): void
    {
        Storage::fake('local');

        $customer = $this->createCustomer();

        $product = $this->createProduct(
            name: 'BizzSoft V5',
            slug: 'bizzsoft-v5'
        );

        $release = $this->createRelease(
            product: $product,
            status: ProductReleaseStatus::Published
        );

        Storage::disk('local')->put(
            $release->file_path,
            'test release contents'
        );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.products.releases.download',
                    [$product, $release]
                )
            )
            ->assertNotFound();
    }

    public function test_owner_cannot_download_draft_product_release(): void
    {
        Storage::fake('local');

        $customer = $this->createCustomer();

        $product = $this->createProduct(
            name: 'BizzSoft V5',
            slug: 'bizzsoft-v5'
        );

        $this->grantOwnership(
            customer: $customer,
            product: $product,
            orderNumber: 'BS-DOWNLOAD-0002'
        );

        $release = $this->createRelease(
            product: $product,
            status: ProductReleaseStatus::Draft
        );

        Storage::disk('local')->put(
            $release->file_path,
            'test release contents'
        );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.products.releases.download',
                    [$product, $release]
                )
            )
            ->assertNotFound();
    }

    public function test_release_cannot_be_downloaded_through_wrong_product(): void
    {
        Storage::fake('local');

        $customer = $this->createCustomer();

        $ownedProduct = $this->createProduct(
            name: 'BizzSoft V5',
            slug: 'bizzsoft-v5'
        );

        $otherProduct = $this->createProduct(
            name: 'BizzSoft CRM',
            slug: 'bizzsoft-crm'
        );

        $this->grantOwnership(
            customer: $customer,
            product: $ownedProduct,
            orderNumber: 'BS-DOWNLOAD-0003'
        );

        $release = $this->createRelease(
            product: $otherProduct,
            status: ProductReleaseStatus::Published
        );

        Storage::disk('local')->put(
            $release->file_path,
            'test release contents'
        );

        $this
            ->actingAs($customer)
            ->get(
                route(
                    'customer.products.releases.download',
                    [$ownedProduct, $release]
                )
            )
            ->assertNotFound();
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();

        Role::findOrCreate('customer');

        $customer->assignRole('customer');

        return $customer;
    }

    private function createProduct(
        string $name,
        string $slug
    ): Product {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'short_description' => 'Business management software.',
            'description' => 'Secure release download test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);
    }

    private function grantOwnership(
        User $customer,
        Product $product,
        string $orderNumber
    ): ProductOwnership {
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::Completed,
            'ordered_at' => now(),
        ]);

        return ProductOwnership::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'granted_at' => now(),
        ]);
    }

    private function createRelease(
        Product $product,
        ProductReleaseStatus $status
    ): ProductRelease {
        return ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/'
                .$product->id
                .'/release.zip',
            'original_name' => 'bizzsoft-v5.0.1.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'status' => $status,
            'released_at' => $status === ProductReleaseStatus::Published
                ? now()
                : null,
        ]);
    }
}