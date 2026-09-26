<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Product snapshot.
             *
             * These values preserve what the customer ordered
             * even if the Product record changes later.
             */
            $table->string('product_name_snapshot');
            $table->string('product_version_snapshot')->nullable();
            $table->decimal('price_snapshot', 12, 2);

            $table->string('status')
                ->default(OrderStatus::Pending->value)
                ->index();

            $table->text('notes')->nullable();

            $table->timestamp('ordered_at');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};