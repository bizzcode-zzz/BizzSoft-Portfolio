<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('built_with')
                ->nullable()
                ->after('demo_url');

            $table->string('server_requirement')
                ->nullable()
                ->after('built_with');

            $table->string('database_system')
                ->nullable()
                ->after('server_requirement');

            $table->string('browser_support')
                ->nullable()
                ->after('database_system');

            $table->json('included_items')
                ->nullable()
                ->after('browser_support');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'built_with',
                'server_requirement',
                'database_system',
                'browser_support',
                'included_items',
            ]);
        });
    }
};