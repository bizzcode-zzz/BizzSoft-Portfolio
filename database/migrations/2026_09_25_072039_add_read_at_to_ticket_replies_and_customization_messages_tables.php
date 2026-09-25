<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->timestamp('read_at')
                ->nullable()
                ->after('message');
        });

        Schema::table('customization_messages', function (Blueprint $table) {
            $table->timestamp('read_at')
                ->nullable()
                ->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });

        Schema::table('customization_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};