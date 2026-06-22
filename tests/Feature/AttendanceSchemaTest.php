<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class AttendanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_migration_defines_documented_columns_indexes_and_retention_boundary(): void
    {
        $this->assertTrue(Schema::hasTable('attendances'));
        $this->assertTrue(Schema::hasColumns('attendances', [
            'id', 'school_id', 'student_id', 'academic_year_id', 'class_id',
            'section_id', 'attendance_date', 'status', 'remarks', 'marked_by',
            'created_at', 'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('attendances', 'deleted_at'));

        $actualIndexes = collect(Schema::getIndexes('attendances'))->pluck('name')->all();

        foreach ([
            'idx_attendances_school_id',
            'idx_attendances_student_id',
            'idx_attendances_academic_year_id',
            'idx_attendances_class_id',
            'idx_attendances_section_id',
            'idx_attendances_attendance_date',
            'idx_attendances_status',
            'idx_attendances_marked_by',
            'uq_attendances_student_date',
        ] as $index) {
            $this->assertContains($index, $actualIndexes, "Missing {$index} on attendances.");
        }
    }

    public function test_model_defaults_deny_and_requires_tenant_context_for_creation(): void
    {
        $school = $this->school('One');
        $structure = $this->createAttendanceStructure($school, 'ONE');

        $this->assertSame(0, Attendance::query()->count());
        $this->assertTenantCreateDenied(fn () => Attendance::create($this->attendancePayload($structure, '2026-04-03')));

        app(TenantContext::class)->runAsPlatform(function () use ($structure): void {
            $this->assertTenantCreateDenied(fn () => Attendance::create($this->attendancePayload($structure, '2026-04-03')));
        });
    }

    public function test_tenant_context_assigns_ownership_filters_records_and_platform_reads_all(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structureOne = $this->createAttendanceStructure($schoolOne, 'ONE', $schoolTwo->id);
        $structureTwo = $this->createAttendanceStructure($schoolTwo, 'TWO', $schoolOne->id);
        $context = app(TenantContext::class);

        $this->assertSame($schoolOne->id, $structureOne['attendance']->school_id);
        $this->assertSame($schoolTwo->id, $structureTwo['attendance']->school_id);

        $context->setTenant($schoolOne->id);
        $this->assertSame([$structureOne['attendance']->id], Attendance::query()->pluck('id')->all());

        $context->setTenant($schoolTwo->id);
        $this->assertSame([$structureTwo['attendance']->id], Attendance::query()->pluck('id')->all());

        $context->setPlatform();
        $this->assertSame(2, Attendance::query()->count());

        $context->clear();
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_attendance_identity_tenant_ownership_and_history_are_immutable(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structure = $this->createAttendanceStructure($schoolOne, 'ONE');

        app(TenantContext::class)->runAsTenant($schoolOne->id, function () use ($schoolTwo, $structure): void {
            $attendance = $structure['attendance']->fresh();
            $attendance->status = Attendance::STATUS_LATE;
            $attendance->remarks = 'Arrived after the attendance start time.';
            $attendance->save();

            $this->assertSame(Attendance::STATUS_LATE, $attendance->fresh()->status);
            $this->assertSame('Arrived after the attendance start time.', $attendance->fresh()->remarks);

            $changes = [
                'student_id' => 999999,
                'academic_year_id' => 999999,
                'class_id' => 999999,
                'section_id' => 999999,
                'attendance_date' => '2026-04-04',
                'marked_by' => 999999,
            ];

            foreach ($changes as $field => $value) {
                $attendance = $attendance->fresh();
                $attendance->{$field} = $value;

                try {
                    $attendance->save();
                    $this->fail("Attendance {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame(
                        'Attendance identity and original marker cannot be changed.',
                        $exception->getMessage(),
                    );
                }
            }

            try {
                $attendance = $attendance->fresh();
                $attendance->school_id = $schoolTwo->id;
                $attendance->save();
                $this->fail('Attendance tenant ownership was changed.');
            } catch (TenantContextException $exception) {
                $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
            }

            try {
                $attendance->fresh()->delete();
                $this->fail('A retained Attendance record was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Attendance records are retained and cannot be deleted.', $exception->getMessage());
            }
        });

        $this->assertDatabaseHas('attendances', [
            'id' => $structure['attendance']->id,
            'school_id' => $schoolOne->id,
            'student_id' => $structure['student']->id,
            'marked_by' => $structure['marker']->id,
            'status' => Attendance::STATUS_LATE,
        ]);
    }

    public function test_relationships_casts_fillable_statuses_and_retained_parents_match_design(): void
    {
        $school = $this->school('One');
        $structure = $this->createAttendanceStructure($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $structure): void {
            $attendance = $structure['attendance']->fresh();

            $this->assertTrue($attendance->school->is($school));
            $this->assertTrue($attendance->student->is($structure['student']));
            $this->assertTrue($attendance->academicYear->is($structure['year']));
            $this->assertTrue($attendance->schoolClass->is($structure['class']));
            $this->assertTrue($attendance->section->is($structure['section']));
            $this->assertTrue($attendance->markedBy->is($structure['marker']));
            $this->assertTrue($school->attendances->first()->is($attendance));
            $this->assertTrue($structure['student']->attendances->first()->is($attendance));
            $this->assertTrue($structure['year']->attendances->first()->is($attendance));
            $this->assertTrue($structure['class']->attendances->first()->is($attendance));
            $this->assertTrue($structure['section']->attendances->first()->is($attendance));
            $this->assertTrue($structure['marker']->markedAttendances->first()->is($attendance));

            $this->assertSame('2026-04-03', $attendance->attendance_date->toDateString());
            $this->assertSame(Attendance::STATUS_PRESENT, $attendance->status);
            $this->assertFalse($attendance->isFillable('school_id'));
            $this->assertTrue($attendance->isFillable('marked_by'));
            $this->assertFalse(method_exists($attendance, 'trashed'));
            $this->assertSame(
                ['present', 'absent', 'leave', 'late', 'holiday'],
                [
                    Attendance::STATUS_PRESENT,
                    Attendance::STATUS_ABSENT,
                    Attendance::STATUS_LEAVE,
                    Attendance::STATUS_LATE,
                    Attendance::STATUS_HOLIDAY,
                ],
            );

            $structure['student']->delete();
            $structure['class']->delete();
            $structure['section']->delete();
            $structure['marker']->delete();
            $attendance = $attendance->fresh();

            $this->assertTrue($attendance->student->is($structure['student']));
            $this->assertTrue($attendance->schoolClass->is($structure['class']));
            $this->assertTrue($attendance->section->is($structure['section']));
            $this->assertTrue($attendance->markedBy->is($structure['marker']));
        });
    }

    public function test_unique_foreign_key_and_restrict_delete_constraints_preserve_integrity(): void
    {
        $school = $this->school('One');
        $structure = $this->createAttendanceStructure($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($structure): void {
            $this->assertQueryFails(fn () => Attendance::create($this->attendancePayload($structure, '2026-04-03')));

            $nextDay = Attendance::create($this->attendancePayload($structure, '2026-04-04'));
            $this->assertSame(Attendance::STATUS_PRESENT, $nextDay->fresh()->status);

            foreach (['student_id', 'academic_year_id', 'class_id', 'section_id', 'marked_by'] as $foreignKey) {
                $payload = $this->attendancePayload($structure, '2026-04-05');
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => Attendance::create($payload));
            }

            $this->assertQueryFails(fn () => DB::table('attendances')->insert([
                ...$this->attendancePayload($structure, '2026-04-05'),
                'school_id' => 999999,
            ]));

            $this->assertQueryFails(fn () => Student::withTrashed()->findOrFail($structure['student']->id)->forceDelete());
            $this->assertQueryFails(fn () => $structure['year']->fresh()->delete());
            $this->assertQueryFails(fn () => SchoolClass::withTrashed()->findOrFail($structure['class']->id)->forceDelete());
            $this->assertQueryFails(fn () => Section::withTrashed()->findOrFail($structure['section']->id)->forceDelete());
            $this->assertQueryFails(fn () => User::withTrashed()->findOrFail($structure['marker']->id)->forceDelete());
        });
    }

    private function assertTenantCreateDenied(callable $create): void
    {
        try {
            $create();
            $this->fail('A strict tenant-owned Attendance record was created without Tenant context.');
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
     *     enrollment: StudentEnrollment,
     *     marker: User,
     *     attendance: Attendance
     * }
     */
    private function createAttendanceStructure(School $school, string $suffix, ?int $forgedSchoolId = null): array
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
                'admission_no' => 'ADM-'.$suffix,
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
                'roll_no' => 'ROLL-'.$suffix,
                'enrollment_date' => '2026-04-02',
            ]);
            $marker = User::factory()->create([
                'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
                'school_id' => $school->id,
                'email' => strtolower('attendance-'.$suffix).'@example.com',
            ]);
            $attendance = Attendance::create([
                ...$this->attendancePayload([
                    'year' => $year,
                    'class' => $schoolClass,
                    'section' => $section,
                    'student' => $student,
                    'marker' => $marker,
                ], '2026-04-03'),
                'school_id' => $forgedSchoolId,
            ])->refresh();

            return [
                'year' => $year,
                'class' => $schoolClass,
                'section' => $section,
                'student' => $student,
                'enrollment' => $enrollment,
                'marker' => $marker,
                'attendance' => $attendance,
            ];
        });
    }

    /**
     * @param  array<string, AcademicYear|SchoolClass|Section|Student|User>  $structure
     * @return array<string, int|string>
     */
    private function attendancePayload(array $structure, string $date): array
    {
        return [
            'student_id' => $structure['student']->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'attendance_date' => $date,
            'marked_by' => $structure['marker']->id,
        ];
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ATT-'.$suffix,
            'email' => strtolower('attendance-'.$suffix).'@school.example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }
}
