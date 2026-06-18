<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index('idx_schools_name');
            $table->string('code', 50)->unique('uq_schools_code');
            $table->string('email', 150)->unique('uq_schools_email');
            $table->string('phone', 30)->nullable()->index('idx_schools_phone');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable()->index('idx_schools_city');
            $table->string('state', 100)->nullable()->index('idx_schools_state');
            $table->string('country', 100)->default('India');
            $table->string('postal_code', 20)->nullable();
            $table->string('principal_name', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('status', 20)->default('active')->index('idx_schools_status');
            $table->timestamp('deactivated_at')->nullable()->index('idx_schools_deactivated_at');
            $table->text('deactivation_reason')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_schools_deleted_at');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique('uq_roles_name');
            $table->string('code', 50)->unique('uq_roles_code');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true)->index('idx_roles_is_system');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index('idx_permissions_name');
            $table->string('code', 100)->unique('uq_permissions_code');
            $table->string('module', 80)->index('idx_permissions_module');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->index('role_id', 'idx_role_permissions_role_id');
            $table->index('permission_id', 'idx_role_permissions_permission_id');
            $table->unique(['role_id', 'permission_id'], 'uq_role_permissions_role_permission');
            $table->foreign('role_id', 'fk_role_permissions_role_id')
                ->references('id')
                ->on('roles')
                ->restrictOnDelete();
            $table->foreign('permission_id', 'fk_role_permissions_permission_id')
                ->references('id')
                ->on('permissions')
                ->restrictOnDelete();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->string('name', 150)->index('idx_users_name');
            $table->string('email', 150)->unique('uq_users_email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 30)->nullable()->index('idx_users_phone');
            $table->string('status', 20)->default('active')->index('idx_users_status');
            $table->timestamp('last_login_at')->nullable()->index('idx_users_last_login_at');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_users_deleted_at');

            $table->index('school_id', 'idx_users_school_id');
            $table->index('role_id', 'idx_users_role_id');
            $table->foreign('school_id', 'fk_users_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('role_id', 'fk_users_role_id')
                ->references('id')
                ->on('roles')
                ->restrictOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('schools');
    }
};
