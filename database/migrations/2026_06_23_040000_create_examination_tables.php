<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('grade', 10)->index('idx_grade_scales_grade');
            $table->decimal('min_percentage', 5, 2)->default(0)->index('idx_grade_scales_min_percentage');
            $table->decimal('max_percentage', 5, 2)->default(100)->index('idx_grade_scales_max_percentage');
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->string('remarks', 150)->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_grade_scales_school_id');
            $table->unique(['school_id', 'grade'], 'uq_grade_scales_school_grade');
            $table->foreign('school_id', 'fk_grade_scales_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('academic_term_id')->nullable();
            $table->string('name', 120)->index('idx_exams_name');
            $table->string('exam_type', 50)->default('term')->index('idx_exams_exam_type');
            $table->date('start_date')->index('idx_exams_start_date');
            $table->date('end_date')->index('idx_exams_end_date');
            $table->string('status', 20)->default('scheduled')->index('idx_exams_status');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index('idx_exams_deleted_at');

            $table->index('school_id', 'idx_exams_school_id');
            $table->index('academic_year_id', 'idx_exams_academic_year_id');
            $table->index('academic_term_id', 'idx_exams_academic_term_id');
            $table->unique(['school_id', 'academic_year_id', 'name'], 'uq_exams_school_year_name');
            $table->foreign('school_id', 'fk_exams_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_exams_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('academic_term_id', 'fk_exams_academic_term_id')
                ->references('id')
                ->on('academic_terms')
                ->restrictOnDelete();
        });

        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->date('exam_date')->nullable()->index('idx_exam_subjects_exam_date');
            $table->decimal('max_marks', 6, 2)->default(100);
            $table->decimal('passing_marks', 6, 2)->default(33);
            $table->timestamps();

            $table->index('school_id', 'idx_exam_subjects_school_id');
            $table->index('exam_id', 'idx_exam_subjects_exam_id');
            $table->index('subject_id', 'idx_exam_subjects_subject_id');
            $table->index('class_id', 'idx_exam_subjects_class_id');
            $table->unique(
                ['exam_id', 'subject_id', 'class_id'],
                'uq_exam_subjects_exam_subject_class',
            );
            $table->foreign('school_id', 'fk_exam_subjects_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('exam_id', 'fk_exam_subjects_exam_id')
                ->references('id')
                ->on('exams')
                ->restrictOnDelete();
            $table->foreign('subject_id', 'fk_exam_subjects_subject_id')
                ->references('id')
                ->on('subjects')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_exam_subjects_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('exam_subject_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->decimal('marks_obtained', 6, 2)->default(0);
            $table->unsignedBigInteger('grade_scale_id')->nullable();
            $table->string('result_status', 20)->default('pending')->index('idx_exam_results_result_status');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('entered_by')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_exam_results_school_id');
            $table->index('exam_id', 'idx_exam_results_exam_id');
            $table->index('exam_subject_id', 'idx_exam_results_exam_subject_id');
            $table->index('student_id', 'idx_exam_results_student_id');
            $table->index('subject_id', 'idx_exam_results_subject_id');
            $table->index('grade_scale_id', 'idx_exam_results_grade_scale_id');
            $table->index('entered_by', 'idx_exam_results_entered_by');
            $table->unique(
                ['exam_subject_id', 'student_id'],
                'uq_exam_results_subject_student',
            );
            $table->foreign('school_id', 'fk_exam_results_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('exam_id', 'fk_exam_results_exam_id')
                ->references('id')
                ->on('exams')
                ->restrictOnDelete();
            $table->foreign('exam_subject_id', 'fk_exam_results_exam_subject_id')
                ->references('id')
                ->on('exam_subjects')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_exam_results_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('subject_id', 'fk_exam_results_subject_id')
                ->references('id')
                ->on('subjects')
                ->restrictOnDelete();
            $table->foreign('grade_scale_id', 'fk_exam_results_grade_scale_id')
                ->references('id')
                ->on('grade_scales')
                ->restrictOnDelete();
            $table->foreign('entered_by', 'fk_exam_results_entered_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('marks_obtained', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0)->index('idx_report_cards_percentage');
            $table->unsignedBigInteger('grade_scale_id')->nullable();
            $table->string('result_status', 20)->default('pending')->index('idx_report_cards_result_status');
            $table->timestamp('generated_at')->nullable()->index('idx_report_cards_generated_at');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->index('school_id', 'idx_report_cards_school_id');
            $table->index('exam_id', 'idx_report_cards_exam_id');
            $table->index('student_id', 'idx_report_cards_student_id');
            $table->index('academic_year_id', 'idx_report_cards_academic_year_id');
            $table->index('class_id', 'idx_report_cards_class_id');
            $table->index('section_id', 'idx_report_cards_section_id');
            $table->index('grade_scale_id', 'idx_report_cards_grade_scale_id');
            $table->index('generated_by', 'idx_report_cards_generated_by');
            $table->unique(['exam_id', 'student_id'], 'uq_report_cards_exam_student');
            $table->foreign('school_id', 'fk_report_cards_school_id')
                ->references('id')
                ->on('schools')
                ->restrictOnDelete();
            $table->foreign('exam_id', 'fk_report_cards_exam_id')
                ->references('id')
                ->on('exams')
                ->restrictOnDelete();
            $table->foreign('student_id', 'fk_report_cards_student_id')
                ->references('id')
                ->on('students')
                ->restrictOnDelete();
            $table->foreign('academic_year_id', 'fk_report_cards_academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('class_id', 'fk_report_cards_class_id')
                ->references('id')
                ->on('classes')
                ->restrictOnDelete();
            $table->foreign('section_id', 'fk_report_cards_section_id')
                ->references('id')
                ->on('sections')
                ->restrictOnDelete();
            $table->foreign('grade_scale_id', 'fk_report_cards_grade_scale_id')
                ->references('id')
                ->on('grade_scales')
                ->restrictOnDelete();
            $table->foreign('generated_by', 'fk_report_cards_generated_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('grade_scales');
    }
};
