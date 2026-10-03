<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\ProductOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class LicenseProductScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_automatically_receives_a_license_product_key(): void
    {
        $product = $this->createProduct('generated-key');

        $this->assertNotNull($product->license_product_key);
        $this->assertTrue(
            Str::isUuid($product->license_product_key)
        );
    }

    public function test_license_product_key_is_immutable_through_the_product_model(): void
    {
        $product = $this->createProduct('immutable-key');
        $originalKey = $product->license_product_key;

        try {
            $product->forceFill([
                'license_product_key' => (string) Str::uuid(),
            ])->save();

            $this->fail('License product key was changed.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'The license product key is immutable.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            $originalKey,
            $product->fresh()->license_product_key
        );
    }

    public function test_license_cannot_be_activated_for_a_different_product(): void
    {
        $licensedProduct = $this->createProduct('licensed-activation');
        $otherProduct = $this->createProduct('other-activation');

        $license = $this->createLicense(
            $licensedProduct,
            'unactivated',
            null
        );

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'product_key' => $otherProduct->license_product_key,
                'domain' => 'company.example',
            ])
            ->assertNotFound()
            ->assertJson([
                'valid' => false,
                'status' => 'invalid_license',
            ]);

        $license->refresh();

        $this->assertSame('unactivated', $license->status);
        $this->assertNull($license->production_domain);
        $this->assertNull($license->activated_at);

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => $license->id,
            'event' => 'product_mismatch',
            'attempted_domain' => 'company.example',
            'http_status' => 404,
        ]);
    }

    public function test_license_cannot_validate_for_a_different_product(): void
    {
        $licensedProduct = $this->createProduct('licensed-validation');
        $otherProduct = $this->createProduct('other-validation');

        $license = $this->createLicense(
            $licensedProduct,
            'active',
            'company.example'
        );

        $beforeValidation = $license->last_validated_at;

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'product_key' => $otherProduct->license_product_key,
                'domain' => 'company.example',
            ])
            ->assertNotFound()
            ->assertJson([
                'valid' => false,
                'status' => 'invalid_license',
            ]);

        $license->refresh();

        $this->assertSame(
            'company.example',
            $license->production_domain
        );
        $this->assertTrue(
            $license->last_validated_at->equalTo($beforeValidation)
        );

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => $license->id,
            'event' => 'product_mismatch',
            'attempted_domain' => 'company.example',
            'http_status' => 404,
        ]);
    }

    private function createProduct(string $slug): Product
    {
        return Product::create([
            'name' => 'Scope '.$slug,
            'slug' => $slug,
            'short_description' => 'License product scope test.',
            'description' => 'License product scope regression product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '1',
        ]);
    }

    private function createLicense(
        Product $product,
        string $status,
        ?string $domain
    ): ProductLicense {
        $customer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'BS-SCOPE-'.Str::upper(Str::random(12)),
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
            'starting_release_id' => null,
            'granted_by' => null,
            'granted_at' => now(),
        ]);

        return ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-SCOPE-'.Str::upper(Str::random(16)),
            'status' => $status,
            'production_domain' => $domain,
            'activated_at' => $domain !== null ? now()->subDay() : null,
            'last_validated_at' => $domain !== null ? now()->subDay() : null,
        ]);
    }
}
