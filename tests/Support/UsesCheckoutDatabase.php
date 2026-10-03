<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

trait UsesCheckoutDatabase
{
    protected function setUpUsesCheckoutDatabase(): void
    {
        // No migrations or surrounding transaction: each test owns a fresh memory-only database.
        config([
            'database.default' => 'customer_checkout_offline',
            'database.connections.customer_checkout_offline' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
            ],
            'session.driver' => 'array', 'cache.default' => 'array', 'logging.default' => 'null',
        ]);
        DB::purge('customer_checkout_offline');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->uuid('license_product_key')->unique();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('status');
            $table->string('version')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->string('product_name_snapshot');
            $table->string('product_version_snapshot')->nullable();
            $table->decimal('price_snapshot', 12, 2);
            $table->string('status');
            $table->dateTime('ordered_at');
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->morphs('payable');
            $table->integer('user_id');
            $table->decimal('amount', 18, 2);
            $table->string('currency');
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });
        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->unique(['name', 'guard_name']);
                $table->timestamps();
            });
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key) {
                $table->unsignedBigInteger($key);
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary([$key, 'model_id', 'model_type']);
            });
        }
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });
        // Empty support tables let the normal Inertia middleware calculate unread counts.
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
        });

        Schema::create('customization_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('title');
            $table->text('description');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('customization_quotes', function (Blueprint $table) {
            $table->id();
            $table->integer('customization_request_id');
            $table->decimal('price', 12, 2);
            $table->text('scope');
            $table->date('estimated_delivery');
            $table->timestamps();
        });

        foreach (['ticket_replies' => 'ticket_id', 'customization_messages' => 'customization_request_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key) {
                $table->id();
                $table->integer('user_id');
                $table->integer($key);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('customization_secure_accesses', function (Blueprint $table) {
            $table->id();
            $table->integer('customization_request_id');
            $table->integer('created_by')->nullable();
            $table->string('direction')->nullable();
            $table->string('type')->nullable();
            $table->string('label')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        // This connection was asserted to be :memory: above; never use a real database here.
        (require base_path('database/migrations/2026_09_30_120000_create_payment_adjustments_table.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDownUsesCheckoutDatabase(): void
    {
        DB::purge('customer_checkout_offline');
    }
}
