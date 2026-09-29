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
use Tests\TestCase;

class LicenseActivationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_license_binds_to_first_domain_and_rejects_a_different_domain(): void
    {
        $customer = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'License activation test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-LICENSE-ACTIVATE-0001',
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

        $license = ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-TEST-AAAA-BBBB-CCCC',
            'status' => 'unactivated',
        ]);

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'status' => 'active',
                'domain' => 'company-a.test',
            ]);

        $license->refresh();

        $this->assertSame('active', $license->status);
        $this->assertSame(
            'company-a.test',
            $license->production_domain
        );

        $this->assertNotNull($license->activated_at);

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'status' => 'active',
                'domain' => 'company-a.test',
            ]);

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'domain' => 'company-b.test',
            ])
            ->assertStatus(409)
            ->assertJson([
                'valid' => false,
                'status' => 'domain_mismatch',
                'domain' => 'company-a.test',
            ]);

        $license->refresh();

        $this->assertSame(
            'company-a.test',
            $license->production_domain
        );
    }

    public function test_revoked_license_cannot_be_activated_again(): void
    {
        $customer = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'Revoked license activation test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-LICENSE-REVOKED-0001',
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

        $license = ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-TEST-RVOK-AAAA-BBBB',
            'status' => 'revoked',
            'production_domain' => 'company-a.test',
            'activated_at' => now()->subDay(),
            'last_validated_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertStatus(403)
            ->assertJson([
                'valid' => false,
                'status' => 'revoked',
                'domain' => 'company-a.test',
            ]);

        $this
            ->postJson('/api/licenses/activate', [
                'license_key' => $license->license_key,
                'domain' => 'company-b.test',
            ])
            ->assertStatus(403)
            ->assertJson([
                'valid' => false,
                'status' => 'revoked',
                'domain' => 'company-a.test',
            ]);
    }
}
