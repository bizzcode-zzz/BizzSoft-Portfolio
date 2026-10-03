<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPriceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_zero_price_product(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Zero Price Product',
                'price' => 0,
                'status' => ProductStatus::Active->value,
                'version' => '1.0.0',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_cannot_change_existing_product_price_to_zero(): void
    {
        $admin = $this->admin();

        $product = Product::create([
            'name' => 'Paid Product',
            'slug' => 'paid-product',
            'price' => '100.00',
            'status' => ProductStatus::Active,
            'version' => '1.0.0',
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), [
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => 0,
                'status' => ProductStatus::Active->value,
                'version' => $product->version,
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('price');

        $this->assertSame(
            '100.00',
            $product->fresh()->price
        );
    }

    public function test_admin_cannot_create_sub_cent_price_product(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Sub Cent Product',
                'price' => '0.001',
                'status' => ProductStatus::Active->value,
                'version' => '1.0.0',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_cannot_change_existing_product_to_sub_cent_price(): void
    {
        $admin = $this->admin();

        $product = Product::create([
            'name' => 'Paid Product',
            'slug' => 'paid-product-sub-cent',
            'price' => '100.00',
            'status' => ProductStatus::Active,
            'version' => '1.0.0',
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), [
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => '0.009',
                'status' => ProductStatus::Active->value,
                'version' => $product->version,
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('price');

        $this->assertSame(
            '100.00',
            $product->fresh()->price
        );
    }
    public function test_smallest_cent_price_is_allowed(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'One Cent Product',
                'price' => '0.01',
                'status' => ProductStatus::Active->value,
                'version' => '1.0.0',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(
            '0.01',
            Product::query()->sole()->price
        );
    }

    public function test_customer_cannot_order_legacy_zero_price_active_product(): void
    {
        $customer = $this->customer();

        $product = Product::create([
            'name' => 'Legacy Zero Price Product',
            'slug' => 'legacy-zero-price-product',
            'price' => '0.00',
            'status' => ProductStatus::Active,
            'version' => '1.0.0',
        ]);

        $this
            ->actingAs($customer)
            ->post(route('customer.orders.store', $product))
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function customer(): User
    {
        Role::findOrCreate('customer');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }
}
