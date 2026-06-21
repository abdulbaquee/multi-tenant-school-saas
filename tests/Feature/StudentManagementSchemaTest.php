<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class StudentManagementSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_migration_defines_documented_tables_columns_and_indexes(): void
    {
        $columns = [
            'students' => [
                'id', 'school_id', 'admission_no', 'first_name', 'last_name',
                'gender', 'date_of_birth', 'photo_path', 'guardian_name',
                'guardian_phone', 'guardian_email', 'address', 'admission_date',
                'status', 'created_at', 'updated_at', 'deleted_at',
            ],
            'student_enrollments' => [
                'id', 'school_id', 'student_id', 'academic_year_id', 'class_id',
                'section_id', 'roll_no', 'enrollment_date', 'status',
                'created_at', 'updated_at',
            ],
        ];

        foreach ($columns as $table => $expectedColumns) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumns($table, $expectedColumns));
        }

        $this->assertFalse(Schema::hasColumn('students', 'roll_no'));

        $expectedIndexes = [
            'students' => [
                'idx_students_school_id', 'idx_students_admission_no',
                'idx_students_first_name', 'idx_students_last_name',
                'idx_students_gender', 'idx_students_date_of_birth',
                'idx_students_guardian_name', 'idx_students_guardian_phone',
                'idx_students_guardian_email', 'idx_students_admission_date',
                'idx_students_status', 'idx_students_deleted_at',
                'uq_students_school_admission_no',
            ],
            'student_enrollments' => [
                'idx_student_enrollments_school_id',
                'idx_student_enrollments_student_id',
                'idx_student_enrollments_academic_year_id',
                'idx_student_enrollments_class_id',
                'idx_student_enrollments_section_id',
                'idx_student_enrollments_roll_no',
                'idx_student_enrollments_enrollment_date',
                'idx_student_enrollments_status',
                'uq_student_enrollments_student_year',
                'uq_student_enrollments_section_roll',
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            $actualNames = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach ($indexes as $index) {
                $this->assertContains($index, $actualNames, "Missing {$index} on {$table}.");
            }
        }
    }

    public function test_models_default_deny_and_require_tenant_context_for_creation(): void
    {
        $school = $this->school('One');
        $structure = $this->createStudentStructure($school, 'ONE');

        $this->assertSame(0, Student::query()->count());
        $this->assertSame(0, StudentEnrollment::query()->count());

        foreach ($this->creationCallbacks($structure, 'UNRESOLVED') as $create) {
            $this->assertTenantCreateDenied($create);
        }

        app(TenantContext::class)->runAsPlatform(function () use ($structure): void {
            foreach ($this->creationCallbacks($structure, 'PLATFORM') as $create) {
                $this->assertTenantCreateDenied($create);
            }
        });
    }

    public function test_tenant_context_assigns_ownership_filters_records_and_platform_reads_all(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structureOne = $this->createStudentStructure($schoolOne, 'ONE', $schoolTwo->id);
        $structureTwo = $this->createStudentStructure($schoolTwo, 'TWO', $schoolOne->id);
        $context = app(TenantContext::class);

        foreach ([$structureOne['student'], $structureOne['enrollment']] as $model) {
            $this->assertSame($schoolOne->id, $model->school_id);
        }

        foreach ([$structureTwo['student'], $structureTwo['enrollment']] as $model) {
            $this->assertSame($schoolTwo->id, $model->school_id);
        }

        $context->setTenant($schoolOne->id);
        $this->assertSame(1, Student::query()->count());
        $this->assertSame(1, StudentEnrollment::query()->count());

        $context->setTenant($schoolTwo->id);
        $this->assertSame(1, Student::query()->count());
        $this->assertSame(1, StudentEnrollment::query()->count());

        $context->setPlatform();
        $this->assertSame(2, Student::query()->count());
        $this->assertSame(2, StudentEnrollment::query()->count());

        $context->clear();
        $this->assertSame(0, Student::query()->count());
        $this->assertSame(0, StudentEnrollment::query()->count());
    }

    public function test_tenant_and_student_enrollment_identity_fields_are_immutable(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structure = $this->createStudentStructure($schoolOne, 'ONE');

        app(TenantContext::class)->runAsTenant($schoolOne->id, function () use ($schoolTwo, $structure): void {
            $student = $structure['student']->fresh();
            $enrollment = $structure['enrollment']->fresh();

            $student->status = Student::STATUS_INACTIVE;
            $student->save();
            $this->assertSame(Student::STATUS_INACTIVE, $student->fresh()->status);

            try {
                $student->admission_no = 'CHANGED';
                $student->save();
                $this->fail('Student admission number was changed.');
            } catch (LogicException $exception) {
                $this->assertSame('Student admission number cannot be changed.', $exception->getMessage());
            }

            $student = $student->fresh();

            try {
                $student->school_id = $schoolTwo->id;
                $student->save();
                $this->fail('Student tenant ownership was changed.');
            } catch (TenantContextException $exception) {
                $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
            }

            $enrollment->status = StudentEnrollment::STATUS_COMPLETED;
            $enrollment->save();
            $this->assertSame(StudentEnrollment::STATUS_COMPLETED, $enrollment->fresh()->status);

            foreach (['student_id', 'academic_year_id', 'class_id', 'section_id', 'roll_no'] as $field) {
                $enrollment = $enrollment->fresh();
                $enrollment->{$field} = $field === 'roll_no' ? 'CHANGED' : 999999;

                try {
                    $enrollment->save();
                    $this->fail("Enrollment {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Student Enrollment placement cannot be changed.', $exception->getMessage());
                }
            }

            try {
                $enrollment = $enrollment->fresh();
                $enrollment->school_id = $schoolTwo->id;
                $enrollment->save();
                $this->fail('Enrollment tenant ownership was changed.');
            } catch (TenantContextException $exception) {
                $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
            }

            try {
                $enrollment->fresh()->delete();
                $this->fail('A retained Student Enrollment was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Student Enrollment records are retained and cannot be deleted.', $exception->getMessage());
            }
        });

        $this->assertDatabaseHas('students', [
            'id' => $structure['student']->id,
            'school_id' => $schoolOne->id,
            'admission_no' => 'ADM-ONE',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $structure['enrollment']->id,
            'school_id' => $schoolOne->id,
            'roll_no' => 'ROLL-ONE',
            'status' => StudentEnrollment::STATUS_COMPLETED,
        ]);
    }

    public function test_relationships_casts_fillable_and_soft_delete_boundaries_match_design(): void
    {
        $school = $this->school('One');
        $structure = $this->createStudentStructure($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $structure): void {
            $student = $structure['student']->fresh();
            $enrollment = $structure['enrollment']->fresh();

            $this->assertTrue($student->school->is($school));
            $this->assertTrue($student->enrollments->first()->is($enrollment));
            $this->assertTrue($enrollment->student->is($student));
            $this->assertTrue($enrollment->academicYear->is($structure['year']));
            $this->assertTrue($enrollment->schoolClass->is($structure['class']));
            $this->assertTrue($enrollment->section->is($structure['section']));
            $this->assertTrue($school->students->first()->is($student));
            $this->assertTrue($school->studentEnrollments->first()->is($enrollment));
            $this->assertTrue($structure['year']->studentEnrollments->first()->is($enrollment));
            $this->assertTrue($structure['class']->studentEnrollments->first()->is($enrollment));
            $this->assertTrue($structure['section']->studentEnrollments->first()->is($enrollment));

            $this->assertSame('2015-05-10', $student->date_of_birth->toDateString());
            $this->assertSame('2026-04-02', $student->admission_date->toDateString());
            $this->assertSame('2026-04-02', $enrollment->enrollment_date->toDateString());
            $this->assertFalse($student->isFillable('school_id'));
            $this->assertFalse($enrollment->isFillable('school_id'));
            $this->assertTrue(method_exists($student, 'trashed'));
            $this->assertFalse(method_exists($enrollment, 'trashed'));

            $unenrolledStudent = Student::create([
                ...$this->studentPayload('ARCHIVE'),
                'admission_no' => 'ADM-ARCHIVE',
            ]);
            $unenrolledStudent->delete();
            $this->assertTrue($unenrolledStudent->trashed());
        });
    }

    public function test_unique_constraints_foreign_keys_and_restrict_delete_preserve_integrity(): void
    {
        $school = $this->school('One');
        $structure = $this->createStudentStructure($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($structure): void {
            $this->assertQueryFails(fn () => Student::create([
                ...$this->studentPayload('DUPLICATE'),
                'admission_no' => $structure['student']->admission_no,
            ]));

            $structure['student']->delete();
            $this->assertQueryFails(fn () => Student::create([
                ...$this->studentPayload('ARCHIVED-DUPLICATE'),
                'admission_no' => $structure['student']->admission_no,
            ]));

            $this->assertQueryFails(fn () => StudentEnrollment::create([
                ...$this->enrollmentPayload($structure, 'ANOTHER'),
                'roll_no' => 'ANOTHER',
            ]));

            $secondStudent = Student::create([
                ...$this->studentPayload('SECOND'),
                'admission_no' => 'ADM-SECOND',
            ]);
            $this->assertQueryFails(fn () => StudentEnrollment::create([
                ...$this->enrollmentPayload($structure, 'ROLL-ONE'),
                'student_id' => $secondStudent->id,
                'roll_no' => 'ROLL-ONE',
            ]));

            $this->assertQueryFails(fn () => StudentEnrollment::create([
                'student_id' => 999999,
                'academic_year_id' => $structure['year']->id,
                'class_id' => $structure['class']->id,
                'section_id' => $structure['section']->id,
                'roll_no' => 'MISSING-STUDENT',
                'enrollment_date' => '2026-04-02',
            ]));

            foreach (['academic_year_id', 'class_id', 'section_id'] as $foreignKey) {
                $payload = $this->enrollmentPayload($structure, 'MISSING-'.$foreignKey);
                $payload['student_id'] = $secondStudent->id;
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => StudentEnrollment::create($payload));
            }

            $this->assertQueryFails(fn () => DB::table('students')->insert([
                ...$this->studentPayload('MISSING-SCHOOL'),
                'school_id' => 999999,
                'admission_no' => 'ADM-MISSING-SCHOOL',
            ]));
            $this->assertQueryFails(fn () => DB::table('student_enrollments')->insert([
                ...$this->enrollmentPayload($structure, 'MISSING-SCHOOL'),
                'school_id' => 999999,
                'student_id' => $secondStudent->id,
            ]));

            $this->assertQueryFails(fn () => Student::withTrashed()->findOrFail($structure['student']->id)->forceDelete());
            $this->assertQueryFails(fn () => $structure['year']->fresh()->delete());
            $this->assertQueryFails(fn () => $structure['class']->fresh()->forceDelete());
            $this->assertQueryFails(fn () => $structure['section']->fresh()->forceDelete());
        });
    }

    /**
     * @param  array<string, AcademicYear|SchoolClass|Section|Student|StudentEnrollment>  $structure
     * @return list<callable(): mixed>
     */
    private function creationCallbacks(array $structure, string $suffix): array
    {
        return [
            fn () => Student::create([
                ...$this->studentPayload($suffix),
                'admission_no' => 'ADM-'.$suffix,
            ]),
            fn () => StudentEnrollment::create([
                ...$this->enrollmentPayload($structure, $suffix),
                'roll_no' => 'ROLL-'.$suffix,
            ]),
        ];
    }

    private function assertTenantCreateDenied(callable $create): void
    {
        try {
            $create();
            $this->fail('A strict tenant-owned Student Management record was created without Tenant context.');
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
     *     student: Student,
     *     enrollment: StudentEnrollment
     * }
     */
    private function createStudentStructure(School $school, string $suffix, ?int $forgedSchoolId = null): array
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
                'code' => 'CLS-'.$suffix,
            ]);
            $section = Section::create([
                'class_id' => $schoolClass->id,
                'name' => 'Section '.$suffix,
            ]);
            $student = Student::create([
                ...$this->studentPayload($suffix),
                'school_id' => $forgedSchoolId,
                'admission_no' => 'ADM-'.$suffix,
            ]);
            $enrollment = StudentEnrollment::create([
                ...$this->enrollmentPayload([
                    'year' => $year,
                    'class' => $schoolClass,
                    'section' => $section,
                    'student' => $student,
                ], 'ROLL-'.$suffix),
                'school_id' => $forgedSchoolId,
                'roll_no' => 'ROLL-'.$suffix,
            ]);

            return [
                'year' => $year,
                'class' => $schoolClass,
                'section' => $section,
                'student' => $student,
                'enrollment' => $enrollment,
            ];
        });
    }

    /**
     * @return array<string, string>
     */
    private function studentPayload(string $suffix): array
    {
        return [
            'first_name' => 'Student',
            'last_name' => $suffix,
            'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
            'date_of_birth' => '2015-05-10',
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '9876500000',
            'guardian_email' => strtolower($suffix).'@guardian.example.com',
            'address' => 'Demonstration address',
            'admission_date' => '2026-04-02',
        ];
    }

    /**
     * @param  array<string, AcademicYear|SchoolClass|Section|Student>  $structure
     * @return array<string, int|string>
     */
    private function enrollmentPayload(array $structure, string $rollNo): array
    {
        return [
            'student_id' => $structure['student']->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'roll_no' => $rollNo,
            'enrollment_date' => '2026-04-02',
        ];
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'STU-'.$suffix,
            'email' => strtolower('student-'.$suffix).'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }
}
