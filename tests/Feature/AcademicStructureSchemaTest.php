<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicStructureSchemaTest extends TestCase
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
            'academic_years' => ['id', 'school_id', 'name', 'start_date', 'end_date', 'is_current', 'status', 'created_at', 'updated_at'],
            'academic_terms' => ['id', 'school_id', 'academic_year_id', 'name', 'term_order', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'],
            'classes' => ['id', 'school_id', 'name', 'code', 'sort_order', 'status', 'created_at', 'updated_at', 'deleted_at'],
            'teachers' => ['id', 'school_id', 'user_id', 'employee_code', 'qualification', 'specialization', 'phone', 'joining_date', 'status', 'created_at', 'updated_at', 'deleted_at'],
            'sections' => ['id', 'school_id', 'class_id', 'teacher_id', 'name', 'capacity', 'status', 'created_at', 'updated_at', 'deleted_at'],
            'subjects' => ['id', 'school_id', 'class_id', 'teacher_id', 'name', 'code', 'subject_type', 'status', 'created_at', 'updated_at', 'deleted_at'],
        ];

        foreach ($columns as $table => $expectedColumns) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumns($table, $expectedColumns));
        }

        $expectedIndexes = [
            'academic_years' => [
                'idx_academic_years_school_id', 'idx_academic_years_start_date',
                'idx_academic_years_is_current', 'idx_academic_years_status',
                'uq_academic_years_school_name',
            ],
            'academic_terms' => [
                'idx_academic_terms_school_id', 'idx_academic_terms_academic_year_id',
                'idx_academic_terms_start_date', 'idx_academic_terms_status',
                'uq_academic_terms_year_name', 'uq_academic_terms_year_order',
            ],
            'classes' => [
                'idx_classes_school_id', 'idx_classes_name', 'idx_classes_sort_order',
                'idx_classes_status', 'idx_classes_deleted_at',
                'uq_classes_school_name', 'uq_classes_school_code',
            ],
            'teachers' => [
                'idx_teachers_school_id', 'idx_teachers_user_id', 'idx_teachers_phone',
                'idx_teachers_joining_date', 'idx_teachers_status',
                'idx_teachers_deleted_at', 'uq_teachers_user_id',
                'uq_teachers_school_employee_code',
            ],
            'sections' => [
                'idx_sections_school_id', 'idx_sections_class_id',
                'idx_sections_teacher_id', 'idx_sections_status',
                'idx_sections_deleted_at', 'uq_sections_class_name',
            ],
            'subjects' => [
                'idx_subjects_school_id', 'idx_subjects_class_id',
                'idx_subjects_teacher_id', 'idx_subjects_name',
                'idx_subjects_subject_type', 'idx_subjects_status',
                'idx_subjects_deleted_at', 'uq_subjects_class_code',
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            $actualNames = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach ($indexes as $index) {
                $this->assertContains($index, $actualNames, "Missing {$index} on {$table}.");
            }
        }
    }

    public function test_every_academic_model_defaults_to_deny_and_requires_tenant_context_for_creation(): void
    {
        $school = $this->school('One');
        $structure = $this->createStructure($school, 'ONE');
        $context = app(TenantContext::class);

        foreach ($this->modelClasses() as $modelClass) {
            $this->assertSame(0, $modelClass::query()->count());
        }

        foreach ($this->creationCallbacks($structure, 'UNRESOLVED') as $create) {
            $this->assertTenantCreateDenied($create);
        }

        $context->setPlatform();

        try {
            foreach ($this->creationCallbacks($structure, 'PLATFORM') as $create) {
                $this->assertTenantCreateDenied($create);
            }
        } finally {
            $context->clear();
        }
    }

    public function test_tenant_context_assigns_ownership_filters_all_models_and_platform_reads_all(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structureOne = $this->createStructure($schoolOne, 'ONE', $schoolTwo->id);
        $structureTwo = $this->createStructure($schoolTwo, 'TWO', $schoolOne->id);
        $context = app(TenantContext::class);

        foreach ($structureOne as $model) {
            $this->assertSame($schoolOne->id, $model->school_id);
        }

        foreach ($structureTwo as $model) {
            $this->assertSame($schoolTwo->id, $model->school_id);
        }

        $context->setTenant($schoolOne->id);
        foreach ($this->modelClasses() as $modelClass) {
            $this->assertSame(1, $modelClass::query()->count());
        }

        $context->setTenant($schoolTwo->id);
        foreach ($this->modelClasses() as $modelClass) {
            $this->assertSame(1, $modelClass::query()->count());
        }

        $context->setPlatform();
        foreach ($this->modelClasses() as $modelClass) {
            $this->assertSame(2, $modelClass::query()->count());
        }

        $context->clear();
        foreach ($this->modelClasses() as $modelClass) {
            $this->assertSame(0, $modelClass::query()->count());
        }
    }

    public function test_tenant_ownership_is_immutable_for_every_academic_model(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $structure = $this->createStructure($schoolOne, 'ONE');
        $context = app(TenantContext::class);
        $context->setTenant($schoolOne->id);

        try {
            foreach ($structure as $model) {
                try {
                    $model = $model->fresh();
                    $model->school_id = $schoolTwo->id;
                    $model->save();
                    $this->fail('Tenant ownership changed for '.$model::class.'.');
                } catch (TenantContextException $exception) {
                    $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
                }
            }
        } finally {
            $context->clear();
        }

        foreach ($structure as $model) {
            $this->assertDatabaseHas($model->getTable(), [
                'id' => $model->id,
                'school_id' => $schoolOne->id,
            ]);
        }
    }

    public function test_relationships_casts_and_soft_delete_boundaries_match_the_design(): void
    {
        $school = $this->school('One');
        $structure = $this->createStructure($school, 'ONE');
        $context = app(TenantContext::class);
        $context->setTenant($school->id);

        try {
            /** @var AcademicYear $year */
            $year = $structure['year']->fresh();
            /** @var AcademicTerm $term */
            $term = $structure['term']->fresh();
            /** @var SchoolClass $schoolClass */
            $schoolClass = $structure['class']->fresh();
            /** @var Teacher $teacher */
            $teacher = $structure['teacher']->fresh();
            /** @var Section $section */
            $section = $structure['section']->fresh();
            /** @var Subject $subject */
            $subject = $structure['subject']->fresh();

            $this->assertTrue($year->school->is($school));
            $this->assertTrue($year->terms->first()->is($term));
            $this->assertTrue($term->academicYear->is($year));
            $this->assertTrue($schoolClass->sections->first()->is($section));
            $this->assertTrue($schoolClass->subjects->first()->is($subject));
            $this->assertTrue($teacher->user->teacherProfile->is($teacher));
            $this->assertTrue($teacher->sections->first()->is($section));
            $this->assertTrue($teacher->subjects->first()->is($subject));
            $this->assertTrue($section->schoolClass->is($schoolClass));
            $this->assertTrue($section->teacher->is($teacher));
            $this->assertTrue($subject->schoolClass->is($schoolClass));
            $this->assertTrue($subject->teacher->is($teacher));

            $this->assertFalse($year->is_current);
            $this->assertSame('2026-04-01', $year->start_date->toDateString());
            $this->assertSame(1, $term->term_order);
            $this->assertSame(1, $schoolClass->sort_order);
            $this->assertSame(40, $section->capacity);
            $this->assertSame('2026-04-01', $teacher->joining_date->toDateString());

            $this->assertFalse(method_exists($year, 'trashed'));
            $this->assertFalse(method_exists($term, 'trashed'));

            foreach ([$section, $subject, $teacher, $schoolClass] as $softDeletable) {
                $softDeletable->delete();
                $this->assertTrue($softDeletable->trashed());
                $this->assertDatabaseHas($softDeletable->getTable(), [
                    'id' => $softDeletable->id,
                ]);
            }
        } finally {
            $context->clear();
        }
    }

    public function test_unique_constraints_and_foreign_keys_reject_invalid_rows(): void
    {
        $school = $this->school('One');
        $structure = $this->createStructure($school, 'ONE');
        $context = app(TenantContext::class);
        $context->setTenant($school->id);

        try {
            try {
                AcademicYear::create([
                    'name' => $structure['year']->name,
                    'start_date' => '2027-04-01',
                    'end_date' => '2028-03-31',
                ]);
                $this->fail('A duplicate tenant Academic Year name was created.');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }

            try {
                AcademicTerm::create([
                    'academic_year_id' => 999999,
                    'name' => 'Invalid Parent',
                    'term_order' => 2,
                    'start_date' => '2026-10-01',
                    'end_date' => '2027-03-31',
                ]);
                $this->fail('An Academic Term with a missing parent was created.');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        } finally {
            $context->clear();
        }
    }

    public function test_active_and_soft_deleted_teacher_profiles_block_user_role_and_school_changes(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $schoolOne, 'teacher@example.com');
        $teacher = $this->createTeacher($schoolOne, $teacherUser, 'EMP-ONE');
        $activityCount = $this->platformCount('activity_logs');
        $auditCount = $this->platformCount('audit_logs');

        $this->actingAs($schoolAdmin)
            ->put(route('users.update', $teacherUser), [
                'name' => $teacherUser->name,
                'email' => $teacherUser->email,
                'role_id' => $this->roleId(Role::ACCOUNTANT),
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertSame($this->roleId(Role::TEACHER), $teacherUser->fresh()->role_id);

        $this->actingAs($this->superAdmin())
            ->put(route('users.update', $teacherUser), [
                'name' => $teacherUser->name,
                'email' => $teacherUser->email,
                'role_id' => $this->roleId(Role::TEACHER),
                'school_id' => $schoolTwo->id,
            ])
            ->assertSessionHasErrors('school_id');

        $this->assertSame($schoolOne->id, $teacherUser->fresh()->school_id);

        app(TenantContext::class)->runAsTenant($schoolOne->id, fn () => $teacher->delete());

        $this->actingAs($schoolAdmin)
            ->put(route('users.update', $teacherUser), [
                'name' => $teacherUser->name,
                'email' => $teacherUser->email,
                'role_id' => $this->roleId(Role::ACCOUNTANT),
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertSame($this->roleId(Role::TEACHER), $teacherUser->fresh()->role_id);
        $this->assertSame($activityCount, $this->platformCount('activity_logs'));
        $this->assertSame($auditCount, $this->platformCount('audit_logs'));
    }

    /**
     * @return array<string, class-string<AcademicYear|AcademicTerm|SchoolClass|Teacher|Section|Subject>>
     */
    private function modelClasses(): array
    {
        return [
            'year' => AcademicYear::class,
            'term' => AcademicTerm::class,
            'class' => SchoolClass::class,
            'teacher' => Teacher::class,
            'section' => Section::class,
            'subject' => Subject::class,
        ];
    }

    /**
     * @param  array<string, AcademicYear|AcademicTerm|SchoolClass|Teacher|Section|Subject>  $structure
     * @return list<callable(): mixed>
     */
    private function creationCallbacks(array $structure, string $suffix): array
    {
        return [
            fn () => AcademicYear::create([
                'name' => 'Year '.$suffix,
                'start_date' => '2030-04-01',
                'end_date' => '2031-03-31',
            ]),
            fn () => AcademicTerm::create([
                'academic_year_id' => $structure['year']->id,
                'name' => 'Term '.$suffix,
                'term_order' => 2,
                'start_date' => '2030-04-01',
                'end_date' => '2030-09-30',
            ]),
            fn () => SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
            ]),
            fn () => Teacher::create([
                'user_id' => $structure['teacher']->user_id,
                'employee_code' => 'EMP-'.$suffix,
            ]),
            fn () => Section::create([
                'class_id' => $structure['class']->id,
                'name' => 'Section '.$suffix,
            ]),
            fn () => Subject::create([
                'class_id' => $structure['class']->id,
                'name' => 'Subject '.$suffix,
                'code' => 'SUB-'.$suffix,
            ]),
        ];
    }

    private function assertTenantCreateDenied(callable $create): void
    {
        try {
            $create();
            $this->fail('A strict tenant-owned Academic Structure record was created without Tenant context.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('Tenant context is required', $exception->getMessage());
        }
    }

    /**
     * @return array{
     *     year: AcademicYear,
     *     term: AcademicTerm,
     *     class: SchoolClass,
     *     teacher: Teacher,
     *     section: Section,
     *     subject: Subject
     * }
     */
    private function createStructure(School $school, string $suffix, ?int $forgedSchoolId = null): array
    {
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, strtolower($suffix).'@example.com');

        return app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $teacherUser, $suffix, $forgedSchoolId): array {
            $forgedSchoolId ??= $school->id + 1000;
            $year = AcademicYear::create([
                'school_id' => $forgedSchoolId,
                'name' => '2026-2027 '.$suffix,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => false,
            ]);
            $term = AcademicTerm::create([
                'school_id' => $forgedSchoolId,
                'academic_year_id' => $year->id,
                'name' => 'Term 1 '.$suffix,
                'term_order' => 1,
                'start_date' => '2026-04-01',
                'end_date' => '2026-09-30',
            ]);
            $schoolClass = SchoolClass::create([
                'school_id' => $forgedSchoolId,
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'sort_order' => 1,
            ]);
            $teacher = Teacher::create([
                'school_id' => $forgedSchoolId,
                'user_id' => $teacherUser->id,
                'employee_code' => 'EMP-'.$suffix,
                'joining_date' => '2026-04-01',
            ]);
            $section = Section::create([
                'school_id' => $forgedSchoolId,
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'A-'.$suffix,
                'capacity' => 40,
            ]);
            $subject = Subject::create([
                'school_id' => $forgedSchoolId,
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'Mathematics '.$suffix,
                'code' => 'MATH-'.$suffix,
                'subject_type' => Subject::TYPE_THEORY,
            ]);

            return [
                'year' => $year,
                'term' => $term,
                'class' => $schoolClass,
                'teacher' => $teacher,
                'section' => $section,
                'subject' => $subject,
            ];
        });
    }

    private function createTeacher(School $school, User $user, string $employeeCode): Teacher
    {
        return app(TenantContext::class)->runAsTenant(
            $school->id,
            fn () => Teacher::create([
                'user_id' => $user->id,
                'employee_code' => $employeeCode,
            ]),
        );
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ACA-'.$suffix,
            'email' => strtolower('academic-'.$suffix).'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => $this->roleId($roleCode),
            'email' => $email,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function roleId(string $code): int
    {
        return (int) Role::query()->where('code', $code)->value('id');
    }

    private function platformCount(string $table): int
    {
        return app(TenantContext::class)->runAsPlatform(
            fn (): int => (int) DB::table($table)->count(),
        );
    }
}
