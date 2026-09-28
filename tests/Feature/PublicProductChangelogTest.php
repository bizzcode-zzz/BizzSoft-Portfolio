<?php

namespace Tests\Feature;

use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProductChangelogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_changelog_shows_only_published_releases_newest_first_without_private_metadata(): void
    {
        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'Public changelog test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $olderRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/'.$product->id.'/5.0.1.zip',
            'original_name' => 'bizzsoft-v5.0.1.zip',
            'file_size' => 900,
            'sha256' => str_repeat('a', 64),
            'release_notes' => 'Initial public release.',
            'upgrade_notes' => 'Private upgrade notes for 5.0.1.',
            'status' => ProductReleaseStatus::Published,
            'released_at' => now()->subDays(2),
        ]);

        $latestRelease = ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.2',
            'file_path' => 'product-releases/'.$product->id.'/5.0.2.zip',
            'original_name' => 'bizzsoft-v5.0.2.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('b', 64),
            'release_notes' => 'Improved dashboard performance.',
            'upgrade_notes' => 'Private upgrade notes for 5.0.2.',
            'status' => ProductReleaseStatus::Published,
            'released_at' => now()->subDay(),
        ]);

        ProductRelease::create([
            'product_id' => $product->id,
            'version' => '5.0.3',
            'file_path' => 'product-releases/'.$product->id.'/5.0.3.zip',
            'original_name' => 'bizzsoft-v5.0.3.zip',
            'file_size' => 1200,
            'sha256' => str_repeat('c', 64),
            'release_notes' => 'Draft-only release notes.',
            'upgrade_notes' => 'Draft-only upgrade notes.',
            'status' => ProductReleaseStatus::Draft,
            'released_at' => null,
        ]);

        $response = $this->get(
            route('products.changelog', $product->slug)
        );

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Products/Changelog')
                ->where('product.name', 'BizzSoft V5')
                ->where('product.slug', 'bizzsoft-v5')
                ->has('product.releases', 2)
                ->where(
                    'product.releases.0.version',
                    $latestRelease->version
                )
                ->where(
                    'product.releases.0.release_notes',
                    'Improved dashboard performance.'
                )
                ->where('product.releases.0.is_latest', true)
                ->has('product.releases.0.released_at')
                ->missing('product.releases.0.file_path')
                ->missing('product.releases.0.original_name')
                ->missing('product.releases.0.file_size')
                ->missing('product.releases.0.sha256')
                ->missing('product.releases.0.upgrade_notes')
                ->where(
                    'product.releases.1.version',
                    $olderRelease->version
                )
                ->where('product.releases.1.is_latest', false)
                ->missing('product.releases.1.file_path')
                ->missing('product.releases.1.original_name')
                ->missing('product.releases.1.file_size')
                ->missing('product.releases.1.sha256')
                ->missing('product.releases.1.upgrade_notes')
        );
    }
}