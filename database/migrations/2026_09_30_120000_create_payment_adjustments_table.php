<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('provider_adjustment_id', 191);
            $table->string('provider_transaction_id', 191);

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->restrictOnDelete();

            $table->dateTime('provider_updated_at', 6)->nullable();
            $table->string('last_event_id', 191);
            $table->json('snapshot');
            $table->json('history');
            $table->boolean('hold_active')->default(false);
            $table->boolean('review_required')->default(true);
            $table->string('decision', 100)->default('review_required');
            $table->timestamps();

            $table->unique(['provider', 'provider_adjustment_id']);
            $table->index(['provider', 'provider_transaction_id']);
            $table->index(['order_id', 'hold_active']);
            $table->index(['review_required', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_adjustments');
    }
};
