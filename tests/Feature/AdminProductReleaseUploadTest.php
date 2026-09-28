<?php

namespace Tests\Feature;

use App\Enums\ProductReleaseStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductReleaseUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_private_product_release_as_draft(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $file = UploadedFile::fake()->create(
            'bizzsoft-v5.0.1.zip',
            1024,
            'application/zip'
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.products.releases.store',
                    $product
                ),
                [
                    'version' => '5.0.1',
                    'release_file' => $file,
                ]
            );

        $response->assertRedirect(
            route('admin.products.show', $product)
        );

        $release = ProductRelease::query()
            ->where('product_id', $product->id)
            ->where('version', '5.0.1')
            ->firstOrFail();

        $this->assertSame(
            $admin->id,
            $release->created_by
        );

        $this->assertSame(
            ProductReleaseStatus::Draft,
            $release->status
        );

        $this->assertSame(
            'bizzsoft-v5.0.1.zip',
            $release->original_name
        );

        $this->assertNull(
            $release->released_at
        );

        $this->assertSame(
            64,
            strlen($release->sha256)
        );

        $this->assertStringStartsWith(
            'product-releases/'.$product->id.'/',
            $release->file_path
        );

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        $disk->assertExists(
            $release->file_path
        );
    }

    public function test_duplicate_product_release_version_is_rejected(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $product = $this->createProduct();

        ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/existing.zip',
            'original_name' => 'existing.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'status' => ProductReleaseStatus::Draft,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.products.show', $product))
            ->post(
                route(
                    'admin.products.releases.store',
                    $product
                ),
                [
                    'version' => '5.0.1',
                    'release_file' => UploadedFile::fake()->create(
                        'duplicate.zip',
                        1024,
                        'application/zip'
                    ),
                ]
            );

        $response
            ->assertRedirect(
                route('admin.products.show', $product)
            )
            ->assertSessionHasErrors('version');

        $this->assertSame(
            1,
            ProductRelease::query()
                ->where('product_id', $product->id)
                ->where('version', '5.0.1')
                ->count()
        );
    }

    public function test_customer_cannot_upload_product_release(): void
    {
        Storage::fake('local');

        $customer = $this->createCustomer();
        $product = $this->createProduct();

        $response = $this
            ->actingAs($customer)
            ->post(
                route(
                    'admin.products.releases.store',
                    $product
                ),
                [
                    'version' => '5.0.1',
                    'release_file' => UploadedFile::fake()->create(
                        'bizzsoft-v5.0.1.zip',
                        1024,
                        'application/zip'
                    ),
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'product_releases',
            0
        );
    }

    public function test_admin_can_publish_draft_product_release(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/release.zip',
            'original_name' => 'release.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'status' => ProductReleaseStatus::Draft,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.products.releases.publish',
                    [$product, $release]
                )
            );

        $response->assertRedirect(
            route('admin.products.show', $product)
        );

        $release->refresh();

        $this->assertSame(
            ProductReleaseStatus::Published,
            $release->status
        );

        $this->assertNotNull(
            $release->released_at
        );
    }

    public function test_published_product_release_cannot_be_published_again(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.1',
            'file_path' => 'product-releases/1/release.zip',
            'original_name' => 'release.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('b', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.products.show', $product))
            ->patch(
                route(
                    'admin.products.releases.publish',
                    [$product, $release]
                )
            );

        $response
            ->assertRedirect(
                route('admin.products.show', $product)
            )
            ->assertSessionHasErrors('release');

        $release->refresh();

        $this->assertSame(
            ProductReleaseStatus::Published,
            $release->status
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
            'description' => 'BizzSoft release upload test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);
    }
}
