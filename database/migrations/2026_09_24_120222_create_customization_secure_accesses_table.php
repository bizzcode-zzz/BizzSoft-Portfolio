<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customization_secure_accesses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customization_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('direction');
            $table->string('type');
            $table->string('label');

            // Sensitive values are encrypted by the model.
            // Nullable because they are permanently purged when access is closed.
            $table->text('login_url')->nullable();
            $table->text('username')->nullable();
            $table->text('secret')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('requested');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['customization_request_id', 'status'],
                'secure_access_request_status_idx'
            );

            $table->index(
                ['customization_request_id', 'created_at'],
                'secure_access_request_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customization_secure_accesses');
    }
};