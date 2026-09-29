<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_license_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_license_id')
                ->nullable()
                ->constrained('product_licenses')
                ->nullOnDelete();

            $table->string('event', 50)
                ->index();

            $table->string('attempted_domain', 253)
                ->nullable()
                ->index();

            $table->char('license_key_fingerprint', 64)
                ->nullable()
                ->index();

            $table->string('ip_address', 45)
                ->nullable();

            $table->string('user_agent', 500)
                ->nullable();

            $table->unsignedSmallInteger('http_status');

            $table->timestamps();

            $table->index([
                'product_license_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_license_activities');
    }
};