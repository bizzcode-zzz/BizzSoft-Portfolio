<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customization_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customization_request_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('price', 12, 2);
            $table->text('scope');
            $table->date('estimated_delivery');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customization_quotes');
    }
};