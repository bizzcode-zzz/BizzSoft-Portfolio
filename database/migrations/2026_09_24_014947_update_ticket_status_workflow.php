<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')
            ->where('status', 'open')
            ->update([
                'status' => 'waiting_for_admin',
            ]);

        Schema::table('tickets', function ($table) {
            $table->string('status')
                ->default('waiting_for_admin')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('tickets')
            ->whereIn('status', [
                'waiting_for_admin',
                'waiting_for_customer',
                'resolved',
                'closed',
            ])
            ->update([
                'status' => 'open',
            ]);

        Schema::table('tickets', function ($table) {
            $table->string('status')
                ->default('open')
                ->change();
        });
    }
};