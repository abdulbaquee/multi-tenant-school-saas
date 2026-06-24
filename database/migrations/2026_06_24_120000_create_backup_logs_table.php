<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('backup_type', 30)->default('manual')->index('idx_backup_logs_backup_type');
            $table->string('backup_scope', 30)->default('platform')->index('idx_backup_logs_backup_scope');
            $table->string('file_path', 255)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('status', 20)->default('pending')->index('idx_backup_logs_status');
            $table->timestamp('started_at')->nullable()->index('idx_backup_logs_started_at');
            $table->timestamp('completed_at')->nullable()->index('idx_backup_logs_completed_at');
            $table->unsignedBigInteger('generated_by')->nullable()->index('idx_backup_logs_generated_by');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_backup_logs_school_id');
            $table->foreign('school_id', 'fk_backup_logs_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('generated_by', 'fk_backup_logs_generated_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
