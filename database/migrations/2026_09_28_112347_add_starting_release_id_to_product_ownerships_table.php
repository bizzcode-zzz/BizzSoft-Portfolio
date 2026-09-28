<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_ownerships', function (Blueprint $table) {
            $table->foreignId('starting_release_id')
                ->nullable()
                ->after('order_id')
                ->constrained('product_releases')
                ->restrictOnDelete();
        });

        DB::table('product_ownerships')
            ->orderBy('id')
            ->chunkById(100, function ($ownerships) {
                foreach ($ownerships as $ownership) {
                    $startingReleaseId = DB::table('product_releases')
                        ->where('product_id', $ownership->product_id)
                        ->where('status', 'published')
                        ->orderBy('released_at')
                        ->orderBy('id')
                        ->value('id');

                    if ($startingReleaseId !== null) {
                        DB::table('product_ownerships')
                            ->where('id', $ownership->id)
                            ->update([
                                'starting_release_id' => $startingReleaseId,
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('product_ownerships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('starting_release_id');
        });
    }
};