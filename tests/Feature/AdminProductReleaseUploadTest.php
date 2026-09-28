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

    public function test_admin_can_replace_published_package_and_record_replacement_time(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $oldPath = 'product-releases/'.$product->id.'/old-release.zip';

        Storage::disk('local')->put(
            $oldPath,
            'old published package'
        );

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.2',
            'file_path' => $oldPath,
            'original_name' => 'old-release.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('a', 64),
            'release_notes' => 'Original notes',
            'upgrade_notes' => 'Original upgrade notes',
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.products.releases.update',
                    [$product, $release]
                ),
                [
                    '_method' => 'PATCH',
                    'version' => '5.0.2',
                    'release_notes' => 'Corrected release notes',
                    'upgrade_notes' => 'Please re-download this package.',
                    'release_file' => UploadedFile::fake()->create(
                        'bizzsoft-v5.0.2-corrected.zip',
                        2048,
                        'application/zip'
                    ),
                ]
            );

        $response->assertRedirect(
            route('admin.products.show', $product)
        );

        $release->refresh();

        $this->assertSame(
            ProductReleaseStatus::Published,
            $release->status
        );

        $this->assertSame(
            'bizzsoft-v5.0.2-corrected.zip',
            $release->original_name
        );

        $this->assertNotSame(
            $oldPath,
            $release->file_path
        );

        $this->assertNotNull(
            $release->package_replaced_at
        );

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        $disk->assertMissing($oldPath);
        $disk->assertExists($release->file_path);
    }

    public function test_published_notes_only_edit_does_not_mark_package_as_replaced(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $path = 'product-releases/'.$product->id.'/published.zip';

        Storage::disk('local')->put(
            $path,
            'published package'
        );

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.2',
            'file_path' => $path,
            'original_name' => 'published.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('b', 64),
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.products.releases.update',
                    [$product, $release]
                ),
                [
                    '_method' => 'PATCH',
                    'version' => '5.0.2',
                    'release_notes' => 'Updated changelog only.',
                    'upgrade_notes' => 'No package replacement required.',
                ]
            )
            ->assertRedirect(
                route('admin.products.show', $product)
            );

        $release->refresh();

        $this->assertSame(
            'Updated changelog only.',
            $release->release_notes
        );

        $this->assertSame(
            $path,
            $release->file_path
        );

        $this->assertNull(
            $release->package_replaced_at
        );

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        $disk->assertExists($path);
    }

    public function test_replacing_draft_package_does_not_create_customer_replacement_warning(): void
    {
        Storage::fake('local');

        $admin = $this->createAdmin();
        $product = $this->createProduct();

        $oldPath = 'product-releases/'.$product->id.'/draft-old.zip';

        Storage::disk('local')->put(
            $oldPath,
            'old draft package'
        );

        $release = ProductRelease::create([
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'version' => '5.0.3',
            'file_path' => $oldPath,
            'original_name' => 'draft-old.zip',
            'file_size' => 1000,
            'sha256' => str_repeat('c', 64),
            'status' => ProductReleaseStatus::Draft,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.products.releases.update',
                    [$product, $release]
                ),
                [
                    '_method' => 'PATCH',
                    'version' => '5.0.3',
                    'release_notes' => 'Draft notes',
                    'upgrade_notes' => null,
                    'release_file' => UploadedFile::fake()->create(
                        'bizzsoft-v5.0.3.zip',
                        2048,
                        'application/zip'
                    ),
                ]
            )
            ->assertRedirect(
                route('admin.products.show', $product)
            );

        $release->refresh();

        $this->assertSame(
            ProductReleaseStatus::Draft,
            $release->status
        );

        $this->assertNull(
            $release->package_replaced_at
        );

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        $disk->assertMissing($oldPath);
        $disk->assertExists($release->file_path);
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
