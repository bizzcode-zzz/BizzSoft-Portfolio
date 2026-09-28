<?php

namespace Tests\Feature;

use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProductReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_page_exposes_only_latest_published_release_information(): void
    {
        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'Public product release test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $publishedRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.2',
            'file_path' => 'product-releases/'.$product->id.'/5.0.2.zip',
            'original_name' => 'bizzsoft-v5.0.2.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'release_notes' => 'Improved dashboard performance.',
            'upgrade_notes' => 'Private customer upgrade instructions.',
            'status' => ProductReleaseStatus::Published,
            'released_at' => now()->subDay(),
        ]);

        ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.3',
            'file_path' => 'product-releases/'.$product->id.'/5.0.3.zip',
            'original_name' => 'bizzsoft-v5.0.3.zip',
            'file_size' => 1200,
            'sha256' => str_repeat('b', 64),
            'release_notes' => 'Draft-only notes.',
            'upgrade_notes' => 'Draft-only upgrade notes.',
            'status' => ProductReleaseStatus::Draft,
            'released_at' => null,
        ]);

        $response = $this->get(
            route('products.show', $product->slug)
        );

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Products/Show')
                ->where(
                    'product.latest_release.version',
                    $publishedRelease->version
                )
                ->where(
                    'product.latest_release.release_notes',
                    'Improved dashboard performance.'
                )
                ->has('product.latest_release.released_at')
                ->missing('product.latest_release.file_path')
                ->missing('product.latest_release.original_name')
                ->missing('product.latest_release.file_size')
                ->missing('product.latest_release.sha256')
                ->missing('product.latest_release.upgrade_notes')
        );
    }
}