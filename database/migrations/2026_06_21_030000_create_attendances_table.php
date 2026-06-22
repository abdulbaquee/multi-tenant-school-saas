<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->date('attendance_date')->index('idx_attendances_attendance_date');
            $table->string('status', 20)->default('present')->index('idx_attendances_status');
            $table->string('remarks', 500)->nullable();
            $table->unsignedBigInteger('marked_by');
            $table->timestamps();

            $table->index('school_id', 'idx_attendances_school_id');
            $table->index('student_id', 'idx_attendances_student_id');
            $table->index('academic_year_id', 'idx_attendances_academic_year_id');
            $table->index('class_id', 'idx_attendances_class_id');
            $table->index('section_id', 'idx_attendances_section_id');
            $table->index('marked_by', 'idx_attendances_marked_by');
            $table->unique(
                ['school_id', 'student_id', 'attendance_date'],
                'uq_attendances_student_date',
            );
            $table->foreign('school_id', 'fk_attendances_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_attendances_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_attendances_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_attendances_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
            $table->foreign('section_id', 'fk_attendances_section_id')
                ->references('id')
                ->on('sections')
                ->restrictOnDelete();
            $table->foreign('marked_by', 'fk_attendances_marked_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
