<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('admission_no', 50)->index('idx_students_admission_no');
            $table->string('first_name', 100)->index('idx_students_first_name');
            $table->string('last_name', 100)->nullable()->index('idx_students_last_name');
            $table->string('gender', 20)->index('idx_students_gender');
            $table->date('date_of_birth')->index('idx_students_date_of_birth');
            $table->string('photo_path')->nullable();
            $table->string('guardian_name', 150)->index('idx_students_guardian_name');
            $table->string('guardian_phone', 30)->index('idx_students_guardian_phone');
            $table->string('guardian_email', 150)->nullable()->index('idx_students_guardian_email');
            $table->text('address')->nullable();
            $table->date('admission_date')->index('idx_students_admission_date');
            $table->string('status', 20)->default('active')->index('idx_students_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_students_deleted_at');

            $table->index('school_id', 'idx_students_school_id');
            $table->unique(['school_id', 'admission_no'], 'uq_students_school_admission_no');
            $table->foreign('school_id', 'fk_students_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->string('roll_no', 50)->index('idx_student_enrollments_roll_no');
            $table->date('enrollment_date')->index('idx_student_enrollments_enrollment_date');
            $table->string('status', 20)->default('active')->index('idx_student_enrollments_status');
            $table->timestamps();

            $table->index('school_id', 'idx_student_enrollments_school_id');
            $table->index('student_id', 'idx_student_enrollments_student_id');
            $table->index('academic_year_id', 'idx_student_enrollments_academic_year_id');
            $table->index('class_id', 'idx_student_enrollments_class_id');
            $table->index('section_id', 'idx_student_enrollments_section_id');
            $table->unique(
                ['student_id', 'academic_year_id'],
                'uq_student_enrollments_student_year',
            );
            $table->unique(
                ['school_id', 'academic_year_id', 'class_id', 'section_id', 'roll_no'],
                'uq_student_enrollments_section_roll',
            );
            $table->foreign('school_id', 'fk_student_enrollments_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_student_enrollments_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_student_enrollments_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_student_enrollments_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
            $table->foreign('section_id', 'fk_student_enrollments_section_id')
                ->references('id')
                ->on('sections')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
    }
};
