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

class LicenseValidationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_license_validates_on_its_bound_domain(): void
    {
        $license = $this->createLicense(
            status: 'active',
            domain: 'company-a.test',
        );

        $this
            ->postJson('/api/licenses/validate', [
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

        $this->assertSame(
            'company-a.test',
            $license->production_domain
        );

        $this->assertNotNull($license->last_validated_at);
    }

    public function test_active_license_rejects_a_different_domain(): void
    {
        $license = $this->createLicense(
            status: 'active',
            domain: 'company-a.test',
        );

        $this
            ->postJson('/api/licenses/validate', [
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

    public function test_revoked_license_cannot_validate(): void
    {
        $license = $this->createLicense(
            status: 'revoked',
            domain: 'company-a.test',
            revoked: true,
        );

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertStatus(403)
            ->assertJson([
                'valid' => false,
                'status' => 'revoked',
                'domain' => 'company-a.test',
            ]);
    }

    public function test_unactivated_license_cannot_validate_or_bind_a_domain(): void
    {
        $license = $this->createLicense(
            status: 'unactivated',
            domain: null,
        );

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertStatus(409)
            ->assertJson([
                'valid' => false,
                'status' => 'unactivated',
            ]);

        $license->refresh();

        $this->assertSame('unactivated', $license->status);
        $this->assertNull($license->production_domain);
        $this->assertNull($license->activated_at);
    }

    public function test_invalid_license_key_is_rejected(): void
    {
        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => 'BIZZ-DOES-NOT-EXIST-0001',
                'domain' => 'company-a.test',
            ])
            ->assertStatus(404)
            ->assertJson([
                'valid' => false,
                'status' => 'invalid_license',
            ]);
    }

    public function test_license_validation_is_rate_limited_per_license_and_ip(): void
    {
        $license = $this->createLicense(
            status: 'active',
            domain: 'company-a.test',
        );

        $license->update([
            'license_key' => 'BIZZ-TEST-RATE-AAAA-BBBB',
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this
                ->postJson('/api/licenses/validate', [
                    'license_key' => $license->license_key,
                    'domain' => 'company-a.test',
                ])
                ->assertOk();
        }

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertStatus(429)
            ->assertJson([
                'valid' => false,
                'status' => 'rate_limited',
            ]);
    }

    public function test_successful_validation_is_logged(): void
    {
        $license = $this->createLicense(
            status: 'active',
            domain: 'company-a.test',
        );

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => $license->id,
            'event' => 'validation_success',
            'attempted_domain' => 'company-a.test',
            'license_key_fingerprint' => hash(
                'sha256',
                $license->license_key
            ),
            'http_status' => 200,
        ]);
    }

    public function test_domain_mismatch_is_logged(): void
    {
        $license = $this->createLicense(
            status: 'active',
            domain: 'company-a.test',
        );

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-b.test',
            ])
            ->assertStatus(409);

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => $license->id,
            'event' => 'domain_mismatch',
            'attempted_domain' => 'company-b.test',
            'license_key_fingerprint' => hash(
                'sha256',
                $license->license_key
            ),
            'http_status' => 409,
        ]);
    }

    public function test_revoked_validation_attempt_is_logged(): void
    {
        $license = $this->createLicense(
            status: 'revoked',
            domain: 'company-a.test',
            revoked: true,
        );

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $license->license_key,
                'domain' => 'company-a.test',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => $license->id,
            'event' => 'revoked_attempt',
            'attempted_domain' => 'company-a.test',
            'license_key_fingerprint' => hash(
                'sha256',
                $license->license_key
            ),
            'http_status' => 403,
        ]);
    }

    public function test_invalid_license_validation_attempt_is_logged(): void
    {
        $licenseKey = 'BIZZ-INVALID-LOGG-AAAA-BBBB';

        $this
            ->postJson('/api/licenses/validate', [
                'license_key' => $licenseKey,
                'domain' => 'unknown-company.test',
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('product_license_activities', [
            'product_license_id' => null,
            'event' => 'invalid_license',
            'attempted_domain' => 'unknown-company.test',
            'license_key_fingerprint' => hash(
                'sha256',
                $licenseKey
            ),
            'http_status' => 404,
        ]);
    }

    private function createLicense(
        string $status,
        ?string $domain,
        bool $revoked = false,
    ): ProductLicense {
        $customer = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'License validation test product.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-LICENSE-VALIDATE-0001',
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
            'license_key' => 'BIZZ-TEST-VALI-AAAA-BBBB',
            'status' => $status,
            'production_domain' => $domain,
            'activated_at' => $domain !== null
                ? now()->subDay()
                : null,
            'last_validated_at' => $domain !== null
                ? now()->subDay()
                : null,
            'revoked_at' => $revoked
                ? now()
                : null,
        ]);
    }
}
