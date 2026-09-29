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
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_issued_product_licenses(): void
    {
        $admin = User::factory()->create();

        Role::findOrCreate('admin');
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'name' => 'License Customer',
            'email' => 'license@example.com',
        ]);

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'Admin license management test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-LICENSE-ADMIN-0001',
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
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-TEST-ADMN-LIC1-0001',
            'status' => 'active',
            'production_domain' => 'company-a.test',
            'activated_at' => now(),
            'last_validated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.licenses.index'));

        $response->assertOk();

        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Admin/Licenses/Index')
                ->has('licenses', 1)
                ->where(
                    'licenses.0.license_key',
                    'BIZZ-TEST-ADMN-LIC1-0001'
                )
                ->where('licenses.0.status', 'active')
                ->where(
                    'licenses.0.production_domain',
                    'company-a.test'
                )
                ->where(
                    'licenses.0.order.order_number',
                    'BS-LICENSE-ADMIN-0001'
                )
                ->where(
                    'licenses.0.customer.email',
                    'license@example.com'
                )
                ->where(
                    'licenses.0.product.name',
                    'BizzSoft V5'
                )
                ->has('licenses.0.activated_at')
                ->has('licenses.0.last_validated_at')
        );
    }

    public function test_admin_can_revoke_license_without_releasing_original_domain(): void
    {
        $admin = User::factory()->create();

        Role::findOrCreate('admin');
        $admin->assignRole('admin');

        $customer = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'License revocation test.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-LICENSE-REVOKE-0001',
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
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $license = ProductLicense::create([
            'product_ownership_id' => $ownership->id,
            'order_id' => $order->id,
            'license_key' => 'BIZZ-TEST-RVOK-ADMN-0001',
            'status' => 'active',
            'production_domain' => 'company-a.test',
            'activated_at' => now()->subDay(),
            'last_validated_at' => now()->subHour(),
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.licenses.index'))
            ->patch(
                route('admin.licenses.revoke', $license),
                ['reason' => 'Refund issued']
            )
            ->assertRedirect(route('admin.licenses.index'));

        $license->refresh();

        $this->assertSame('revoked', $license->status);
        $this->assertSame('company-a.test', $license->production_domain);
        $this->assertSame($admin->id, $license->revoked_by);
        $this->assertSame('Refund issued', $license->revocation_reason);
        $this->assertNotNull($license->revoked_at);
    }
}
