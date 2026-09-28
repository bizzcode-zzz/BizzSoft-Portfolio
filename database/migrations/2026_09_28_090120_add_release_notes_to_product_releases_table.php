<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_releases', function (Blueprint $table) {
            $table->text('release_notes')
                ->nullable()
                ->after('sha256');

            $table->text('upgrade_notes')
                ->nullable()
                ->after('release_notes');
        });
    }

    public function down(): void
    {
        Schema::table('product_releases', function (Blueprint $table) {
            $table->dropColumn([
                'release_notes',
                'upgrade_notes',
            ]);
        });
    }
};