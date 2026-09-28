<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_releases', function (Blueprint $table) {
            $table->timestamp('package_replaced_at')
                ->nullable()
                ->after('upgrade_notes');
        });
    }

    public function down(): void
    {
        Schema::table('product_releases', function (Blueprint $table) {
            $table->dropColumn('package_replaced_at');
        });
    }
};