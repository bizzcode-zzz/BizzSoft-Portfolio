<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_releases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('version', 100);

            $table->string('file_path');
            $table->string('original_name');

            $table->unsignedBigInteger('file_size');

            $table->char('sha256', 64);

            $table->string('status', 20)
                ->default('draft');

            $table->timestamp('released_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['product_id', 'version'],
                'product_release_product_version_unique'
            );

            $table->index(
                ['product_id', 'status'],
                'product_release_product_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_releases');
    }
};