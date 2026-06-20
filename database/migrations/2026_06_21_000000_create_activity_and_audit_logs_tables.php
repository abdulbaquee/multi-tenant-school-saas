<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('module', 80)->index('idx_activity_logs_module');
            $table->string('action', 80)->index('idx_activity_logs_action');
            $table->text('description')->nullable();
            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable()->index('idx_activity_logs_ip_address');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index('idx_activity_logs_created_at');

            $table->index('school_id', 'idx_activity_logs_school_id');
            $table->index('user_id', 'idx_activity_logs_user_id');
            $table->index(['subject_type', 'subject_id'], 'idx_activity_logs_subject');
            $table->foreign('school_id', 'fk_activity_logs_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('user_id', 'fk_activity_logs_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 50)->index('idx_audit_logs_event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable()->index('idx_audit_logs_ip_address');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index('idx_audit_logs_created_at');

            $table->index('school_id', 'idx_audit_logs_school_id');
            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
            $table->foreign('school_id', 'fk_audit_logs_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('user_id', 'fk_audit_logs_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('activity_logs');
    }
};
