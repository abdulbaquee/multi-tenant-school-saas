<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\GradeScale;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ExamSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_migration_defines_documented_examination_tables_columns_indexes_and_retention_boundaries(): void
    {
        $expectedColumns = [
            'grade_scales' => [
                'id', 'school_id', 'grade', 'min_percentage', 'max_percentage',
                'grade_point', 'remarks', 'created_at', 'updated_at',
            ],
            'exams' => [
                'id', 'school_id', 'academic_year_id', 'academic_term_id', 'name',
                'exam_type', 'start_date', 'end_date', 'status',
                'created_at', 'updated_at', 'deleted_at',
            ],
            'exam_subjects' => [
                'id', 'school_id', 'exam_id', 'subject_id', 'class_id', 'exam_date',
                'max_marks', 'passing_marks', 'created_at', 'updated_at',
            ],
            'exam_results' => [
                'id', 'school_id', 'exam_id', 'exam_subject_id', 'student_id',
                'subject_id', 'marks_obtained', 'grade_scale_id', 'result_status',
                'remarks', 'entered_by', 'created_at', 'updated_at',
            ],
            'report_cards' => [
                'id', 'school_id', 'exam_id', 'student_id', 'academic_year_id',
                'class_id', 'section_id', 'total_marks', 'marks_obtained',
                'percentage', 'grade_scale_id', 'result_status', 'generated_at',
                'generated_by', 'created_at', 'updated_at',
            ],
        ];

        foreach ($expectedColumns as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumns($table, $columns));
        }

        $this->assertFalse(Schema::hasColumn('exam_results', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('report_cards', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('grade_scales', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('exam_subjects', 'deleted_at'));

        $expectedIndexes = [
            'grade_scales' => [
                'idx_grade_scales_school_id',
                'idx_grade_scales_grade',
                'idx_grade_scales_min_percentage',
                'idx_grade_scales_max_percentage',
                'uq_grade_scales_school_grade',
            ],
            'exams' => [
                'idx_exams_school_id',
                'idx_exams_academic_year_id',
                'idx_exams_academic_term_id',
                'idx_exams_name',
                'idx_exams_exam_type',
                'idx_exams_start_date',
                'idx_exams_end_date',
                'idx_exams_status',
                'idx_exams_deleted_at',
                'uq_exams_school_year_name',
            ],
            'exam_subjects' => [
                'idx_exam_subjects_school_id',
                'idx_exam_subjects_exam_id',
                'idx_exam_subjects_subject_id',
                'idx_exam_subjects_class_id',
                'idx_exam_subjects_exam_date',
                'uq_exam_subjects_exam_subject_class',
            ],
            'exam_results' => [
                'idx_exam_results_school_id',
                'idx_exam_results_exam_id',
                'idx_exam_results_exam_subject_id',
                'idx_exam_results_student_id',
                'idx_exam_results_subject_id',
                'idx_exam_results_grade_scale_id',
                'idx_exam_results_result_status',
                'idx_exam_results_entered_by',
                'uq_exam_results_subject_student',
            ],
            'report_cards' => [
                'idx_report_cards_school_id',
                'idx_report_cards_exam_id',
                'idx_report_cards_student_id',
                'idx_report_cards_academic_year_id',
                'idx_report_cards_class_id',
                'idx_report_cards_section_id',
                'idx_report_cards_percentage',
                'idx_report_cards_grade_scale_id',
                'idx_report_cards_result_status',
                'idx_report_cards_generated_at',
                'idx_report_cards_generated_by',
                'uq_report_cards_exam_student',
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            $actualIndexes = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach ($indexes as $index) {
                $this->assertContains($index, $actualIndexes, "Missing {$index} on {$table}.");
            }
        }
    }

    public function test_examination_models_default_deny_and_require_tenant_context_for_creation(): void
    {
        $school = $this->school('One');
        $graph = $this->createExamGraph($school, 'ONE');

        $this->assertSame(0, GradeScale::query()->count());
        $this->assertTenantCreateDenied(fn () => GradeScale::create($this->gradeScalePayload()));
        $this->assertTenantCreateDenied(fn () => Exam::create($this->examPayload($graph)));
        $this->assertTenantCreateDenied(fn () => ExamSubject::create($this->examSubjectPayload($graph)));
        $this->assertTenantCreateDenied(fn () => ExamResult::create($this->examResultPayload($graph)));
        $this->assertTenantCreateDenied(fn () => ReportCard::create($this->reportCardPayload($graph)));

        app(TenantContext::class)->runAsPlatform(function () use ($graph): void {
            $this->assertTenantCreateDenied(fn () => GradeScale::create($this->gradeScalePayload()));
            $this->assertTenantCreateDenied(fn () => Exam::create($this->examPayload($graph)));
            $this->assertTenantCreateDenied(fn () => ExamSubject::create($this->examSubjectPayload($graph)));
            $this->assertTenantCreateDenied(fn () => ExamResult::create($this->examResultPayload($graph)));
            $this->assertTenantCreateDenied(fn () => ReportCard::create($this->reportCardPayload($graph)));
        });
    }

    public function test_tenant_context_assigns_ownership_filters_examination_records_and_platform_reads_all(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $graphOne = $this->createExamGraph($schoolOne, 'ONE', $schoolTwo->id);
        $graphTwo = $this->createExamGraph($schoolTwo, 'TWO', $schoolOne->id);
        $context = app(TenantContext::class);

        foreach (['gradeScale', 'exam', 'examSubject', 'examResult', 'reportCard'] as $key) {
            $this->assertSame($schoolOne->id, $graphOne[$key]->school_id);
            $this->assertSame($schoolTwo->id, $graphTwo[$key]->school_id);
        }

        $models = [
            GradeScale::class => 'gradeScale',
            Exam::class => 'exam',
            ExamSubject::class => 'examSubject',
            ExamResult::class => 'examResult',
            ReportCard::class => 'reportCard',
        ];

        $context->setTenant($schoolOne->id);
        foreach ($models as $model => $key) {
            $this->assertSame([$graphOne[$key]->id], $model::query()->pluck('id')->all());
        }

        $context->setTenant($schoolTwo->id);
        foreach ($models as $model => $key) {
            $this->assertSame([$graphTwo[$key]->id], $model::query()->pluck('id')->all());
        }

        $context->setPlatform();
        foreach (array_keys($models) as $model) {
            $this->assertSame(2, $model::query()->count());
        }

        $context->clear();
        foreach (array_keys($models) as $model) {
            $this->assertSame(0, $model::query()->count());
        }
    }

    public function test_examination_scope_tenant_ownership_and_retained_history_are_immutable(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $graph = $this->createExamGraph($schoolOne, 'ONE');

        app(TenantContext::class)->runAsTenant($schoolOne->id, function () use ($schoolTwo, $graph): void {
            $gradeScale = $graph['gradeScale']->fresh();
            $gradeScale->min_percentage = '90.00';
            $gradeScale->save();
            $this->assertSame('90.00', $gradeScale->fresh()->min_percentage);

            $gradeScale = $gradeScale->fresh();
            $gradeScale->grade = 'A';

            try {
                $gradeScale->save();
                $this->fail('Grade Scale grade was changed.');
            } catch (LogicException $exception) {
                $this->assertSame('Grade Scale identity cannot be changed.', $exception->getMessage());
            }

            $exam = $graph['exam']->fresh();
            $exam->status = Exam::STATUS_ONGOING;
            $exam->save();
            $this->assertSame(Exam::STATUS_ONGOING, $exam->fresh()->status);

            foreach (['academic_year_id', 'academic_term_id', 'name'] as $field) {
                $exam = $exam->fresh();
                $exam->{$field} = $field === 'name' ? 'Changed Exam' : 999999;

                try {
                    $exam->save();
                    $this->fail("Exam {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Exam scope cannot be changed.', $exception->getMessage());
                }
            }

            $examSubject = $graph['examSubject']->fresh();
            $examSubject->max_marks = '75.00';
            $examSubject->save();
            $this->assertSame('75.00', $examSubject->fresh()->max_marks);

            foreach (['exam_id', 'subject_id', 'class_id'] as $field) {
                $examSubject = $examSubject->fresh();
                $examSubject->{$field} = 999999;

                try {
                    $examSubject->save();
                    $this->fail("Exam Subject {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Exam Subject scope cannot be changed.', $exception->getMessage());
                }
            }

            $examResult = $graph['examResult']->fresh();
            $examResult->marks_obtained = '82.00';
            $examResult->result_status = ExamResult::STATUS_PASS;
            $examResult->remarks = 'Corrected marks.';
            $examResult->save();
            $this->assertSame('82.00', $examResult->fresh()->marks_obtained);

            foreach (['exam_id', 'exam_subject_id', 'student_id', 'subject_id', 'entered_by'] as $field) {
                $examResult = $examResult->fresh();
                $examResult->{$field} = 999999;

                try {
                    $examResult->save();
                    $this->fail("Exam Result {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Exam Result identity cannot be changed.', $exception->getMessage());
                }
            }

            $reportCard = $graph['reportCard']->fresh();
            $reportCard->marks_obtained = '400.00';
            $reportCard->percentage = '80.00';
            $reportCard->result_status = ReportCard::STATUS_PASS;
            $reportCard->save();
            $this->assertSame('400.00', $reportCard->fresh()->marks_obtained);

            foreach (['exam_id', 'student_id', 'academic_year_id', 'class_id', 'section_id', 'generated_by'] as $field) {
                $reportCard = $reportCard->fresh();
                $reportCard->{$field} = 999999;

                try {
                    $reportCard->save();
                    $this->fail("Report Card {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Report Card generation scope cannot be changed.', $exception->getMessage());
                }
            }

            foreach ([GradeScale::class, Exam::class, ExamSubject::class, ExamResult::class, ReportCard::class] as $model) {
                $record = match ($model) {
                    GradeScale::class => $graph['gradeScale']->fresh(),
                    Exam::class => $graph['exam']->fresh(),
                    ExamSubject::class => $graph['examSubject']->fresh(),
                    ExamResult::class => $graph['examResult']->fresh(),
                    ReportCard::class => $graph['reportCard']->fresh(),
                };
                $record->school_id = $schoolTwo->id;

                try {
                    $record->save();
                    $this->fail("{$model} tenant ownership was changed.");
                } catch (TenantContextException $exception) {
                    $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
                }
            }

            try {
                $graph['examResult']->fresh()->delete();
                $this->fail('A retained Exam Result was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Exam Result records are retained and cannot be deleted.', $exception->getMessage());
            }

            try {
                $graph['reportCard']->fresh()->delete();
                $this->fail('A retained Report Card was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Report Card records are retained and cannot be deleted.', $exception->getMessage());
            }
        });
    }

    public function test_relationships_casts_fillable_statuses_and_retained_parents_match_design(): void
    {
        $school = $this->school('One');
        $graph = $this->createExamGraph($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $graph): void {
            $gradeScale = $graph['gradeScale']->fresh();
            $exam = $graph['exam']->fresh();
            $examSubject = $graph['examSubject']->fresh();
            $examResult = $graph['examResult']->fresh();
            $reportCard = $graph['reportCard']->fresh();

            $this->assertTrue($gradeScale->school->is($school));
            $this->assertTrue($gradeScale->examResults->first()->is($examResult));
            $this->assertTrue($gradeScale->reportCards->first()->is($reportCard));
            $this->assertTrue($exam->school->is($school));
            $this->assertTrue($exam->academicYear->is($graph['year']));
            $this->assertTrue($exam->examSubjects->first()->is($examSubject));
            $this->assertTrue($exam->examResults->first()->is($examResult));
            $this->assertTrue($exam->reportCards->first()->is($reportCard));
            $this->assertTrue($examSubject->school->is($school));
            $this->assertTrue($examSubject->exam->is($exam));
            $this->assertTrue($examSubject->subject->is($graph['subject']));
            $this->assertTrue($examSubject->schoolClass->is($graph['class']));
            $this->assertTrue($examSubject->examResults->first()->is($examResult));
            $this->assertTrue($examResult->school->is($school));
            $this->assertTrue($examResult->exam->is($exam));
            $this->assertTrue($examResult->examSubject->is($examSubject));
            $this->assertTrue($examResult->student->is($graph['student']));
            $this->assertTrue($examResult->subject->is($graph['subject']));
            $this->assertTrue($examResult->gradeScale->is($gradeScale));
            $this->assertTrue($examResult->enteredBy->is($graph['teacherUser']));
            $this->assertTrue($reportCard->school->is($school));
            $this->assertTrue($reportCard->exam->is($exam));
            $this->assertTrue($reportCard->student->is($graph['student']));
            $this->assertTrue($reportCard->academicYear->is($graph['year']));
            $this->assertTrue($reportCard->schoolClass->is($graph['class']));
            $this->assertTrue($reportCard->section->is($graph['section']));
            $this->assertTrue($reportCard->gradeScale->is($gradeScale));
            $this->assertTrue($reportCard->generatedBy->is($graph['adminUser']));

            $this->assertTrue($school->gradeScales->first()->is($gradeScale));
            $this->assertTrue($school->exams->first()->is($exam));
            $this->assertTrue($school->examSubjects->first()->is($examSubject));
            $this->assertTrue($school->examResults->first()->is($examResult));
            $this->assertTrue($school->reportCards->first()->is($reportCard));
            $this->assertTrue($graph['year']->exams->first()->is($exam));
            $this->assertTrue($graph['year']->reportCards->first()->is($reportCard));
            $this->assertTrue($graph['class']->examSubjects->first()->is($examSubject));
            $this->assertTrue($graph['class']->reportCards->first()->is($reportCard));
            $this->assertTrue($graph['section']->reportCards->first()->is($reportCard));
            $this->assertTrue($graph['subject']->examSubjects->first()->is($examSubject));
            $this->assertTrue($graph['subject']->examResults->first()->is($examResult));
            $this->assertTrue($graph['student']->examResults->first()->is($examResult));
            $this->assertTrue($graph['student']->reportCards->first()->is($reportCard));
            $this->assertTrue($graph['teacherUser']->enteredExamResults->first()->is($examResult));
            $this->assertTrue($graph['adminUser']->generatedReportCards->first()->is($reportCard));

            $this->assertSame('91.00', $gradeScale->min_percentage);
            $this->assertSame('100.00', $gradeScale->max_percentage);
            $this->assertSame('2026-06-01', $exam->start_date->toDateString());
            $this->assertSame('2026-06-15', $exam->end_date->toDateString());
            $this->assertSame('2026-06-10', $examSubject->exam_date->toDateString());
            $this->assertSame('100.00', $examSubject->max_marks);
            $this->assertSame('33.00', $examSubject->passing_marks);
            $this->assertSame('78.50', $examResult->marks_obtained);
            $this->assertSame('500.00', $reportCard->total_marks);
            $this->assertSame('392.50', $reportCard->marks_obtained);
            $this->assertSame('78.50', $reportCard->percentage);
            $this->assertSame('2026-06-16 10:00:00', $reportCard->generated_at->format('Y-m-d H:i:s'));

            $this->assertFalse($gradeScale->isFillable('school_id'));
            $this->assertFalse($exam->isFillable('school_id'));
            $this->assertFalse($examSubject->isFillable('school_id'));
            $this->assertFalse($examResult->isFillable('school_id'));
            $this->assertFalse($reportCard->isFillable('school_id'));
            $this->assertTrue(method_exists($exam, 'trashed'));
            $this->assertFalse(method_exists($gradeScale, 'trashed'));
            $this->assertFalse(method_exists($examSubject, 'trashed'));
            $this->assertFalse(method_exists($examResult, 'trashed'));
            $this->assertFalse(method_exists($reportCard, 'trashed'));

            $this->assertSame(['term', 'unit_test', 'final', 'other'], [
                Exam::TYPE_TERM,
                Exam::TYPE_UNIT_TEST,
                Exam::TYPE_FINAL,
                Exam::TYPE_OTHER,
            ]);
            $this->assertSame(['scheduled', 'ongoing', 'completed', 'cancelled'], [
                Exam::STATUS_SCHEDULED,
                Exam::STATUS_ONGOING,
                Exam::STATUS_COMPLETED,
                Exam::STATUS_CANCELLED,
            ]);
            $this->assertSame(['pending', 'pass', 'fail', 'absent'], [
                ExamResult::STATUS_PENDING,
                ExamResult::STATUS_PASS,
                ExamResult::STATUS_FAIL,
                ExamResult::STATUS_ABSENT,
            ]);
            $this->assertSame(['pending', 'pass', 'fail'], [
                ReportCard::STATUS_PENDING,
                ReportCard::STATUS_PASS,
                ReportCard::STATUS_FAIL,
            ]);

            $exam->delete();
            $graph['subject']->delete();
            $graph['class']->delete();
            $graph['student']->delete();
            $graph['teacherUser']->delete();
            $graph['adminUser']->delete();

            $examResult = $examResult->fresh();
            $reportCard = $reportCard->fresh();

            $this->assertTrue($examResult->exam->is($exam));
            $this->assertTrue($examResult->student->is($graph['student']));
            $this->assertTrue($examResult->subject->is($graph['subject']));
            $this->assertTrue($examResult->enteredBy->is($graph['teacherUser']));
            $this->assertTrue($reportCard->exam->is($exam));
            $this->assertTrue($reportCard->student->is($graph['student']));
            $this->assertTrue($reportCard->schoolClass->is($graph['class']));
            $this->assertTrue($reportCard->section->is($graph['section']));
            $this->assertTrue($reportCard->generatedBy->is($graph['adminUser']));
        });
    }

    public function test_unique_foreign_key_and_restrict_delete_constraints_preserve_examination_integrity(): void
    {
        $school = $this->school('One');
        $graph = $this->createExamGraph($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($graph): void {
            $this->assertQueryFails(fn () => GradeScale::create($this->gradeScalePayload()));
            $this->assertQueryFails(fn () => Exam::create($this->examPayload($graph)));
            $this->assertQueryFails(fn () => ExamSubject::create($this->examSubjectPayload($graph)));
            $this->assertQueryFails(fn () => ExamResult::create($this->examResultPayload($graph)));
            $this->assertQueryFails(fn () => ReportCard::create($this->reportCardPayload($graph)));

            foreach (['academic_year_id', 'academic_term_id'] as $foreignKey) {
                $payload = $this->examPayload($graph);
                $payload[$foreignKey] = 999999;
                $payload['name'] = 'Forged Exam '.$foreignKey;

                $this->assertQueryFails(fn () => Exam::create($payload));
            }

            foreach (['exam_id', 'subject_id', 'class_id'] as $foreignKey) {
                $payload = $this->examSubjectPayload($graph);
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => ExamSubject::create($payload));
            }

            foreach (['exam_id', 'exam_subject_id', 'student_id', 'subject_id', 'grade_scale_id', 'entered_by'] as $foreignKey) {
                $payload = $this->examResultPayload($graph);
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => ExamResult::create($payload));
            }

            foreach (['exam_id', 'student_id', 'academic_year_id', 'class_id', 'section_id', 'grade_scale_id', 'generated_by'] as $foreignKey) {
                $payload = $this->reportCardPayload($graph);
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => ReportCard::create($payload));
            }

            $this->assertQueryFails(fn () => DB::table('grade_scales')->insert([
                'school_id' => 999999,
                'grade' => 'Z',
                'min_percentage' => 0,
                'max_percentage' => 10,
            ]));

            $this->assertQueryFails(fn () => Exam::withTrashed()->findOrFail($graph['exam']->id)->forceDelete());
            $this->assertQueryFails(fn () => GradeScale::query()->whereKey($graph['gradeScale']->id)->delete());
            $this->assertQueryFails(fn () => Student::withTrashed()->findOrFail($graph['student']->id)->forceDelete());
            $this->assertQueryFails(fn () => SchoolClass::withTrashed()->findOrFail($graph['class']->id)->forceDelete());
            $this->assertQueryFails(fn () => Subject::withTrashed()->findOrFail($graph['subject']->id)->forceDelete());
            $this->assertQueryFails(fn () => User::withTrashed()->findOrFail($graph['teacherUser']->id)->forceDelete());
        });
    }

    private function assertTenantCreateDenied(callable $create): void
    {
        try {
            $create();
            $this->fail('A strict tenant-owned Examination record was created without Tenant context.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('Tenant context is required', $exception->getMessage());
        }
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('A database integrity constraint was not enforced.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @return array{
     *     year: AcademicYear,
     *     class: SchoolClass,
     *     section: Section,
     *     subject: Subject,
     *     student: Student,
     *     enrollment: StudentEnrollment,
     *     teacherUser: User,
     *     adminUser: User,
     *     gradeScale: GradeScale,
     *     exam: Exam,
     *     examSubject: ExamSubject,
     *     examResult: ExamResult,
     *     reportCard: ReportCard
     * }
     */
    private function createExamGraph(School $school, string $suffix, ?int $forgedSchoolId = null): array
    {
        return app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $suffix, $forgedSchoolId): array {
            $forgedSchoolId ??= $school->id + 1000;
            $year = AcademicYear::create([
                'name' => '2026-2027 '.$suffix,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
            ]);
            $schoolClass = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'EXM-'.$suffix,
            ]);
            $section = Section::create([
                'class_id' => $schoolClass->id,
                'name' => 'Section '.$suffix,
            ]);
            $teacherUser = User::factory()->create([
                'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                'school_id' => $school->id,
                'email' => strtolower('exam-teacher-'.$suffix).'@example.com',
            ]);
            $teacher = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'TCH-'.$suffix,
                'joining_date' => '2026-04-01',
            ]);
            $subject = Subject::create([
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'Mathematics '.$suffix,
                'code' => 'MATH-'.$suffix,
            ]);
            $student = Student::create([
                'admission_no' => 'EXM-ADM-'.$suffix,
                'first_name' => 'Student',
                'last_name' => $suffix,
                'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
                'date_of_birth' => '2015-05-10',
                'guardian_name' => 'Guardian '.$suffix,
                'guardian_phone' => '9876500000',
                'admission_date' => '2026-04-02',
            ]);
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $schoolClass->id,
                'section_id' => $section->id,
                'roll_no' => 'EXM-ROLL-'.$suffix,
                'enrollment_date' => '2026-04-02',
            ]);
            $adminUser = User::factory()->create([
                'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
                'school_id' => $school->id,
                'email' => strtolower('exam-admin-'.$suffix).'@example.com',
            ]);
            $gradeScale = GradeScale::create([
                ...$this->gradeScalePayload(),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $exam = Exam::create([
                ...$this->examPayload(['year' => $year]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $examSubject = ExamSubject::create([
                ...$this->examSubjectPayload([
                    'exam' => $exam,
                    'subject' => $subject,
                    'class' => $schoolClass,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $examResult = ExamResult::create([
                ...$this->examResultPayload([
                    'exam' => $exam,
                    'examSubject' => $examSubject,
                    'student' => $student,
                    'subject' => $subject,
                    'gradeScale' => $gradeScale,
                    'teacherUser' => $teacherUser,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $reportCard = ReportCard::create([
                ...$this->reportCardPayload([
                    'exam' => $exam,
                    'student' => $student,
                    'year' => $year,
                    'class' => $schoolClass,
                    'section' => $section,
                    'gradeScale' => $gradeScale,
                    'adminUser' => $adminUser,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();

            return [
                'year' => $year,
                'class' => $schoolClass,
                'section' => $section,
                'subject' => $subject,
                'student' => $student,
                'enrollment' => $enrollment,
                'teacherUser' => $teacherUser,
                'adminUser' => $adminUser,
                'gradeScale' => $gradeScale,
                'exam' => $exam,
                'examSubject' => $examSubject,
                'examResult' => $examResult,
                'reportCard' => $reportCard,
            ];
        });
    }

    /**
     * @return array{grade: string, min_percentage: string, max_percentage: string, grade_point: string, remarks: string}
     */
    private function gradeScalePayload(): array
    {
        return [
            'grade' => 'A+',
            'min_percentage' => '91.00',
            'max_percentage' => '100.00',
            'grade_point' => '10.00',
            'remarks' => 'Outstanding performance.',
        ];
    }

    /**
     * @param  array<string, AcademicYear>  $graph
     * @return array<string, int|string|null>
     */
    private function examPayload(array $graph): array
    {
        return [
            'academic_year_id' => $graph['year']->id,
            'academic_term_id' => null,
            'name' => 'Mid Term Exam',
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'status' => Exam::STATUS_SCHEDULED,
        ];
    }

    /**
     * @param  array<string, Exam|SchoolClass|Subject>  $graph
     * @return array<string, int|string>
     */
    private function examSubjectPayload(array $graph): array
    {
        return [
            'exam_id' => $graph['exam']->id,
            'subject_id' => $graph['subject']->id,
            'class_id' => $graph['class']->id,
            'exam_date' => '2026-06-10',
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ];
    }

    /**
     * @param  array<string, Exam|ExamSubject|GradeScale|Student|Subject|User>  $graph
     * @return array<string, int|string>
     */
    private function examResultPayload(array $graph): array
    {
        return [
            'exam_id' => $graph['exam']->id,
            'exam_subject_id' => $graph['examSubject']->id,
            'student_id' => $graph['student']->id,
            'subject_id' => $graph['subject']->id,
            'marks_obtained' => '78.50',
            'grade_scale_id' => $graph['gradeScale']->id,
            'result_status' => ExamResult::STATUS_PASS,
            'remarks' => 'Good progress.',
            'entered_by' => $graph['teacherUser']->id,
        ];
    }

    /**
     * @param  array<string, Exam|GradeScale|SchoolClass|Section|Student|User|AcademicYear>  $graph
     * @return array<string, int|string>
     */
    private function reportCardPayload(array $graph): array
    {
        return [
            'exam_id' => $graph['exam']->id,
            'student_id' => $graph['student']->id,
            'academic_year_id' => $graph['year']->id,
            'class_id' => $graph['class']->id,
            'section_id' => $graph['section']->id,
            'total_marks' => '500.00',
            'marks_obtained' => '392.50',
            'percentage' => '78.50',
            'grade_scale_id' => $graph['gradeScale']->id,
            'result_status' => ReportCard::STATUS_PASS,
            'generated_at' => '2026-06-16 10:00:00',
            'generated_by' => $graph['adminUser']->id,
        ];
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'EXM-'.$suffix,
            'email' => strtolower('exam-'.$suffix).'@school.example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }
}
