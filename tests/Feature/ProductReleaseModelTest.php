<?php

namespace Tests\Feature;

use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReleaseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_release_relationships_and_casts_work(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct();

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/bizzsoft-v5.0.1.zip',
            'original_name' => 'bizzsoft-v5.0.1.zip',
            'file_size' => 1048576,
            'sha256' => str_repeat('a', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        $this->assertTrue(
            $product->releases()
                ->whereKey($release->id)
                ->exists()
        );

        $this->assertTrue(
            $admin->createdProductReleases()
                ->whereKey($release->id)
                ->exists()
        );

        $this->assertTrue(
            $release->product->is($product)
        );

        $this->assertTrue(
            $release->creator->is($admin)
        );

        $this->assertSame(
            ProductReleaseStatus::Published,
            $release->status
        );

        $this->assertNotNull(
            $release->released_at
        );

        $this->assertSame(
            1048576,
            $release->file_size
        );
    }

    public function test_product_version_can_only_have_one_release_record(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct();

        ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/first.zip',
            'original_name' => 'first.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('b', 64),
            'status' => ProductReleaseStatus::Draft,
        ]);

        $this->expectException(QueryException::class);

        ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/second.zip',
            'original_name' => 'second.zip',
            'file_size' => 2000,
            'sha256' => str_repeat('c', 64),
            'status' => ProductReleaseStatus::Draft,
        ]);
    }

    private function createProduct(): Product
    {
        return Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft product release test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);
    }
}