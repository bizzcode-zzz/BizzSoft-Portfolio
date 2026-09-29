<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            $table->timestamp('revoked_at')
                ->nullable()
                ->after('last_validated_at');

            $table->foreignId('revoked_by')
                ->nullable()
                ->after('revoked_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('revocation_reason')
                ->nullable()
                ->after('revoked_by');

            $table->dropColumn('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            $table->timestamp('deactivated_at')
                ->nullable()
                ->after('last_validated_at');

            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn([
                'revoked_at',
                'revocation_reason',
            ]);
        });
    }
};