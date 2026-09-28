<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ownerships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('granted_at');

            $table->timestamps();

            $table->unique('order_id');

            $table->unique(
                ['user_id', 'product_id'],
                'product_ownership_user_product_unique'
            );

            $table->index(
                ['user_id', 'granted_at'],
                'product_ownership_user_granted_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ownerships');
    }
};
