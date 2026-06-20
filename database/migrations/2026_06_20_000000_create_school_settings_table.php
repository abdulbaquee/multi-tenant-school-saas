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
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('logo_path')->nullable();
            $table->string('timezone', 80)->default('Asia/Kolkata');
            $table->string('currency', 10)->default('INR');
            $table->unsignedTinyInteger('academic_year_start_month')->default(4);
            $table->time('attendance_start_time')->nullable();
            $table->string('grading_system', 50)->default('percentage');
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_school_settings_school_id');
            $table->unique('school_id', 'uq_school_settings_school_id');
            $table->foreign('school_id', 'fk_school_settings_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
