<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_ownership_id')
                ->constrained('product_ownerships')
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->constrained()
                ->restrictOnDelete()
                ->unique();

            $table->string('license_key')
                ->unique();

            $table->string('status')
                ->default('unactivated')
                ->index();

            $table->string('production_domain')
                ->nullable()
                ->index();

            $table->timestamp('activated_at')
                ->nullable();

            $table->timestamp('last_validated_at')
                ->nullable();

            $table->timestamp('deactivated_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['product_ownership_id', 'status'],
                'product_license_ownership_status_idx'
            );
        });

        DB::table('product_ownerships')
            ->orderBy('id')
            ->chunkById(100, function ($ownerships) {
                foreach ($ownerships as $ownership) {
                    do {
                        $token = strtoupper(Str::random(16));

                        $licenseKey = 'BIZZ-'.implode(
                            '-',
                            str_split($token, 4)
                        );
                    } while (
                        DB::table('product_licenses')
                            ->where('license_key', $licenseKey)
                            ->exists()
                    );

                    DB::table('product_licenses')->insert([
                        'product_ownership_id' => $ownership->id,
                        'order_id' => $ownership->order_id,
                        'license_key' => $licenseKey,
                        'status' => 'unactivated',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_licenses');
    }
};