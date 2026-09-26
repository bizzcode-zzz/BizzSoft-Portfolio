<?php

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->default(0);

            $table->string('status')
                ->default(ProductStatus::Draft->value)
                ->index();

            $table->string('version')->nullable();

            $table->string('thumbnail_path')->nullable();
            $table->string('demo_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};