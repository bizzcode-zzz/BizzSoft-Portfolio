<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'payment_webhook_events',
            function (Blueprint $table): void {
                $table->id();

                $table->string('provider', 50);
                $table->string('event_id', 191);
                $table->string('event_type', 100);

                $table->string(
                    'provider_payment_id',
                    191
                )->nullable();

                $table->dateTime('occurred_at')
                    ->nullable();

                $table->dateTime('processed_at')
                    ->nullable();

                $table->dateTime('failed_at')
                    ->nullable();

                $table->text('failure_message')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'provider',
                    'event_id',
                ]);

                $table->index([
                    'provider',
                    'provider_payment_id',
                ]);

                $table->index([
                    'provider',
                    'event_type',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'payment_webhook_events'
        );
    }
};
