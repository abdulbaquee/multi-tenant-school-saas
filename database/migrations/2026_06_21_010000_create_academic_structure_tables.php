<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 50);
            $table->date('start_date')->index('idx_academic_years_start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false)->index('idx_academic_years_is_current');
            $table->string('status', 20)->default('active')->index('idx_academic_years_status');
            $table->timestamps();

            $table->index('school_id', 'idx_academic_years_school_id');
            $table->unique(['school_id', 'name'], 'uq_academic_years_school_name');
            $table->foreign('school_id', 'fk_academic_years_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('name', 80);
            $table->unsignedTinyInteger('term_order')->default(1);
            $table->date('start_date')->index('idx_academic_terms_start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active')->index('idx_academic_terms_status');
            $table->timestamps();

            $table->index('school_id', 'idx_academic_terms_school_id');
            $table->index('academic_year_id', 'idx_academic_terms_academic_year_id');
            $table->unique(['academic_year_id', 'name'], 'uq_academic_terms_year_name');
            $table->unique(['academic_year_id', 'term_order'], 'uq_academic_terms_year_order');
            $table->foreign('school_id', 'fk_academic_terms_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_academic_terms_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 80)->index('idx_classes_name');
            $table->string('code', 30);
            $table->unsignedSmallInteger('sort_order')->default(0)->index('idx_classes_sort_order');
            $table->string('status', 20)->default('active')->index('idx_classes_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_classes_deleted_at');

            $table->index('school_id', 'idx_classes_school_id');
            $table->unique(['school_id', 'name'], 'uq_classes_school_name');
            $table->unique(['school_id', 'code'], 'uq_classes_school_code');
            $table->foreign('school_id', 'fk_classes_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('user_id');
            $table->string('employee_code', 50);
            $table->string('qualification', 150)->nullable();
            $table->string('specialization', 150)->nullable();
            $table->string('phone', 30)->nullable()->index('idx_teachers_phone');
            $table->date('joining_date')->nullable()->index('idx_teachers_joining_date');
            $table->string('status', 20)->default('active')->index('idx_teachers_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_teachers_deleted_at');

            $table->index('school_id', 'idx_teachers_school_id');
            $table->index('user_id', 'idx_teachers_user_id');
            $table->unique('user_id', 'uq_teachers_user_id');
            $table->unique(['school_id', 'employee_code'], 'uq_teachers_school_employee_code');
            $table->foreign('school_id', 'fk_teachers_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('user_id', 'fk_teachers_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('name', 50);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status', 20)->default('active')->index('idx_sections_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_sections_deleted_at');

            $table->index('school_id', 'idx_sections_school_id');
            $table->index('class_id', 'idx_sections_class_id');
            $table->index('teacher_id', 'idx_sections_teacher_id');
            $table->unique(['class_id', 'name'], 'uq_sections_class_name');
            $table->foreign('school_id', 'fk_sections_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_sections_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
            $table->foreign('teacher_id', 'fk_sections_teacher_id')
                ->references('id')
                ->on('teachers')
                ->restrictOnDelete();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('name', 120)->index('idx_subjects_name');
            $table->string('code', 50);
            $table->string('subject_type', 30)->default('theory')->index('idx_subjects_subject_type');
            $table->string('status', 20)->default('active')->index('idx_subjects_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_subjects_deleted_at');

            $table->index('school_id', 'idx_subjects_school_id');
            $table->index('class_id', 'idx_subjects_class_id');
            $table->index('teacher_id', 'idx_subjects_teacher_id');
            $table->unique(['class_id', 'code'], 'uq_subjects_class_code');
            $table->foreign('school_id', 'fk_subjects_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_subjects_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
            $table->foreign('teacher_id', 'fk_subjects_teacher_id')
                ->references('id')
                ->on('teachers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('academic_years');
    }
};
