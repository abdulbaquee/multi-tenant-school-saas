<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\SecurityLogService;
use App\Services\StudentEnrollmentService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StudentEnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_only_school_admin_can_open_or_mutate_enrollment_workflows(): void
    {
        $school = $this->school('One');
        $structure = $this->structure($school, 'ONE');
        $student = $this->student($school, 'ONE');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $superAdmin = $this->superAdmin();

        $this->get(route('student-enrollments.create', $student))->assertRedirect(route('login'));

        foreach ([$teacher, $accountant, $superAdmin] as $actor) {
            $this->actingAs($actor)
                ->get(route('student-enrollments.create', $student))
                ->assertForbidden();
            $this->actingAs($actor)
                ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
                ->assertForbidden();
            $this->actingAs($actor)->patch(route('students.transfer', $student))->assertForbidden();
            $this->actingAs($actor)->patch(route('students.graduate', $student))->assertForbidden();
        }

        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_school_admin_can_create_an_immutable_tenant_owned_enrollment(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee(route('student-enrollments.create', $student), false);
        $this->actingAs($admin)
            ->get(route('student-enrollments.create', $student))
            ->assertOk()
            ->assertSee($structure['year']->name)
            ->assertSee($structure['class']->name)
            ->assertSee($structure['section']->name);

        $response = $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure));
        $enrollment = $this->tenant($school, fn () => StudentEnrollment::query()->firstOrFail());

        $response->assertRedirect(route('students.show', $student));
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'roll_no' => 'ROLL-ONE',
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('ROLL-ONE')
            ->assertSee(route('student-enrollments.complete', $enrollment), false)
            ->assertSee(route('students.transfer', $student), false)
            ->assertSee(route('students.graduate', $student), false);

        [$activities, $audits] = $this->tenant($school, fn () => [
            ActivityLog::query()->where('subject_id', $enrollment->id)->get(),
            AuditLog::query()->where('auditable_id', $enrollment->id)->get(),
        ]);
        $this->assertCount(1, $activities);
        $this->assertCount(1, $audits);
        $serialized = $audits->toJson();
        $this->assertStringContainsString('ROLL-ONE', $serialized);
        $this->assertStringNotContainsString($student->guardian_name, $serialized);
        $this->assertStringNotContainsString($student->guardian_phone, $serialized);
        $this->assertStringNotContainsString('date_of_birth', $serialized);
    }

    public function test_creation_rejects_controlled_fields_invalid_dates_and_ineligible_state(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');

        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), [
                ...$this->enrollmentPayload($structure),
                'roll_no' => str_repeat('R', 51),
                'enrollment_date' => $structure['year']->start_date->subDay()->toDateString(),
                'student_id' => $student->id + 999,
                'school_id' => $school->id + 999,
                'status' => StudentEnrollment::STATUS_COMPLETED,
            ])
            ->assertSessionHasErrors(['roll_no', 'student_id', 'school_id', 'status']);
        $this->assertDatabaseCount('student_enrollments', 0);

        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), [
                ...$this->enrollmentPayload($structure),
                'enrollment_date' => $structure['year']->start_date->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('enrollment_date');
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), [
                ...$this->enrollmentPayload($structure),
                'enrollment_date' => $student->admission_date->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('enrollment_date');

        $this->tenant($school, fn () => $student->forceFill(['status' => Student::STATUS_INACTIVE])->save());
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertForbidden();
    }

    public function test_creation_rejects_cross_tenant_and_invalid_parent_relationships(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $studentA = $this->student($schoolA, 'A');
        $structureA = $this->structure($schoolA, 'A');
        $structureB = $this->structure($schoolB, 'B');

        foreach ([
            ['academic_year_id' => $structureB['year']->id],
            ['class_id' => $structureB['class']->id],
            ['section_id' => $structureB['section']->id],
        ] as $forgedParent) {
            $this->actingAs($adminA)
                ->post(route('student-enrollments.store', $studentA), [
                    ...$this->enrollmentPayload($structureA),
                    ...$forgedParent,
                ])
                ->assertNotFound();
        }

        $otherClass = $this->tenant($schoolA, fn () => SchoolClass::create([
            'name' => 'Other Class',
            'code' => 'OTHER',
            'sort_order' => 2,
            'status' => SchoolClass::STATUS_ACTIVE,
        ]));
        $this->actingAs($adminA)
            ->post(route('student-enrollments.store', $studentA), [
                ...$this->enrollmentPayload($structureA),
                'class_id' => $otherClass->id,
            ])
            ->assertSessionHasErrors('section_id');

        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_creation_rejects_inactive_or_archived_academic_parents(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');

        $this->tenant($school, fn () => $structure['year']->forceFill(['is_current' => false])->save());
        $this->actingAs($admin)
            ->get(route('student-enrollments.create', $student))
            ->assertOk()
            ->assertSee(route('academic-years.index'), false);
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();

        $this->tenant($school, function () use ($structure): void {
            $structure['year']->forceFill([
                'is_current' => true,
                'status' => AcademicYear::STATUS_INACTIVE,
            ])->save();
        });
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();

        $this->tenant($school, function () use ($structure): void {
            $structure['year']->forceFill(['status' => AcademicYear::STATUS_ACTIVE])->save();
            $structure['class']->forceFill(['status' => SchoolClass::STATUS_INACTIVE])->save();
        });
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();

        $this->tenant($school, function () use ($structure): void {
            $structure['class']->forceFill(['status' => SchoolClass::STATUS_ACTIVE])->save();
            $structure['class']->delete();
        });
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();

        $this->tenant($school, function () use ($structure): void {
            $structure['class']->restore();
            $structure['section']->forceFill(['status' => Section::STATUS_INACTIVE])->save();
        });
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();

        $this->tenant($school, function () use ($structure): void {
            $structure['section']->forceFill(['status' => Section::STATUS_ACTIVE])->save();
            $structure['section']->delete();
        });
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), $this->enrollmentPayload($structure))
            ->assertNotFound();
        $this->assertDatabaseCount('student_enrollments', 0);
    }

    public function test_creation_rejects_duplicate_year_roll_and_existing_active_placement(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $structure = $this->structure($school, 'ONE');
        $student = $this->student($school, 'ONE');
        $otherStudent = $this->student($school, 'TWO');
        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');

        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), [
                ...$this->enrollmentPayload($structure),
                'roll_no' => 'ROLL-NEW',
            ])
            ->assertSessionHasErrors('student_id');

        $this->tenant($school, fn () => $this->enrollmentStatus($enrollment, StudentEnrollment::STATUS_COMPLETED));
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $student), [
                ...$this->enrollmentPayload($structure),
                'roll_no' => 'ROLL-NEW',
            ])
            ->assertSessionHasErrors('academic_year_id');
        $this->actingAs($admin)
            ->post(route('student-enrollments.store', $otherStudent), $this->enrollmentPayload($structure))
            ->assertSessionHasErrors('roll_no');

        $this->assertDatabaseCount('student_enrollments', 1);
    }

    public function test_completion_is_terminal_retained_and_leaves_student_active(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');
        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');

        $this->actingAs($admin)
            ->patch(route('student-enrollments.complete', $enrollment), [
                'status' => StudentEnrollment::STATUS_TRANSFERRED,
            ])
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);
        $this->actingAs($admin)
            ->patch(route('student-enrollments.complete', $enrollment))
            ->assertRedirect(route('students.show', $student));
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'status' => StudentEnrollment::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'status' => Student::STATUS_ACTIVE,
        ]);
        $this->actingAs($admin)
            ->patch(route('student-enrollments.complete', $enrollment))
            ->assertForbidden();
        $this->delete('/student-enrollments/'.$enrollment->id)->assertNotFound();
        $this->put('/student-enrollments/'.$enrollment->id, [
            'roll_no' => 'FORGED',
        ])->assertNotFound();

        $inactiveStudent = $this->student($school, 'INACTIVE');
        $inactiveEnrollment = $this->enrollment($school, $inactiveStudent, $structure, 'ROLL-INACTIVE');
        $this->tenant($school, fn () => $inactiveStudent->forceFill(['status' => Student::STATUS_INACTIVE])->save());
        $this->actingAs($admin)
            ->patch(route('student-enrollments.complete', $inactiveEnrollment))
            ->assertForbidden();
    }

    public function test_transfer_updates_student_and_enrollment_atomically_without_cross_tenant_copy(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');
        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');

        $this->actingAs($admin)
            ->patch(route('students.transfer', $student))
            ->assertRedirect(route('students.show', $student));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_TRANSFERRED]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'status' => StudentEnrollment::STATUS_TRANSFERRED,
        ]);
        $this->assertDatabaseCount('students', 1);
        $this->actingAs($admin)->patch(route('students.transfer', $student))->assertForbidden();
    }

    public function test_graduation_completes_active_enrollment_and_terminalizes_student(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');
        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');

        $this->actingAs($admin)
            ->patch(route('students.graduate', $student))
            ->assertRedirect(route('students.show', $student));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_GRADUATED]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'status' => StudentEnrollment::STATUS_COMPLETED,
        ]);
        $this->actingAs($admin)->get(route('students.edit', $student))->assertForbidden();
    }

    public function test_terminal_actions_require_exactly_one_active_enrollment(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');

        $this->actingAs($admin)
            ->patch(route('students.transfer', $student))
            ->assertSessionHasErrors('status');

        $first = $this->structure($school, 'ONE');
        $second = $this->structure($school, 'TWO', false);
        $this->enrollment($school, $student, $first, 'ROLL-ONE');
        $this->enrollment($school, $student, $second, 'ROLL-TWO');

        $this->actingAs($admin)
            ->patch(route('students.graduate', $student))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_ACTIVE]);
        $this->assertDatabaseCount('student_enrollments', 2);
    }

    public function test_cross_tenant_enrollment_routes_and_direct_services_fail_closed(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $studentB = $this->student($schoolB, 'B');
        $structureB = $this->structure($schoolB, 'B');
        $enrollmentB = $this->enrollment($schoolB, $studentB, $structureB, 'ROLL-B');

        $this->actingAs($adminA)->get(route('student-enrollments.create', $studentB))->assertNotFound();
        $this->actingAs($adminA)
            ->post(route('student-enrollments.store', $studentB), $this->enrollmentPayload($structureB))
            ->assertNotFound();
        $this->actingAs($adminA)
            ->patch(route('student-enrollments.complete', $enrollmentB))
            ->assertNotFound();
        $this->actingAs($adminA)->patch(route('students.transfer', $studentB))->assertNotFound();
        $this->actingAs($adminA)->patch(route('students.graduate', $studentB))->assertNotFound();

        $service = app(StudentEnrollmentService::class);
        $context = app(TenantContext::class);
        foreach ([
            fn () => $service->formOptions($studentB, $adminB),
            fn () => $service->create($studentB, $this->enrollmentPayload($structureB), $adminB),
            fn () => $context->runAsPlatform(fn () => $service->formOptions($studentB, $adminB)),
            fn () => $context->runAsPlatform(fn () => $service->create(
                $studentB,
                $this->enrollmentPayload($structureB),
                $this->superAdmin(),
            )),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->formOptions($studentB, $adminB)),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->complete($enrollmentB, $adminA)),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->transfer($studentB, $adminA)),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->graduate($studentB, $adminA)),
            fn () => $context->runAsTenant($schoolB->id, fn () => $service->formOptions($studentB, $this->superAdmin())),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_permission_revocation_removes_enrollment_actions_immediately(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');
        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $createPermission = Permission::query()->where('code', 'students.create')->firstOrFail();
        $role->permissions()->detach($createPermission);
        $this->actingAs($admin)->get(route('student-enrollments.create', $student))->assertForbidden();

        $updatePermission = Permission::query()->where('code', 'students.update')->firstOrFail();
        $role->permissions()->detach($updatePermission);
        $this->actingAs($admin)
            ->patch(route('student-enrollments.complete', $enrollment))
            ->assertForbidden();
        $this->actingAs($admin)->patch(route('students.transfer', $student))->assertForbidden();
        $this->actingAs($admin)->patch(route('students.graduate', $student))->assertForbidden();
    }

    public function test_logging_failure_rolls_back_creation_and_coupled_lifecycle_changes(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $structure = $this->structure($school, 'ONE');
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced Enrollment audit failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(StudentEnrollmentService::class);

        try {
            $this->tenant($school, fn () => $service->create($student, $this->enrollmentPayload($structure), $admin));
            $this->fail('The forced audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced Enrollment audit failure.', $exception->getMessage());
        }
        $this->assertDatabaseCount('student_enrollments', 0);

        $enrollment = $this->enrollment($school, $student, $structure, 'ROLL-ONE');
        $lifecycleLogger = Mockery::mock(SecurityLogService::class);
        $lifecycleLogger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $lifecycleLogger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced lifecycle audit failure.'));
        $this->app->instance(SecurityLogService::class, $lifecycleLogger);
        $lifecycleService = app(StudentEnrollmentService::class);

        try {
            $this->tenant($school, fn () => $lifecycleService->transfer($student, $admin));
            $this->fail('The forced lifecycle audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced lifecycle audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_ACTIVE]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function school(string $suffix, array $attributes = []): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ENROLL-'.$suffix,
            'email' => strtolower('enroll-'.$suffix).'@example.com',
            'status' => School::STATUS_ACTIVE,
            ...$attributes,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'name' => str($roleCode)->replace('_', ' ')->title()->toString(),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()
            ->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))
            ->firstOrFail();
    }

    private function student(School $school, string $suffix): Student
    {
        return $this->tenant($school, fn () => Student::create([
            'admission_no' => 'ADM-'.$suffix,
            'first_name' => 'Student '.$suffix,
            'last_name' => 'Example',
            'gender' => Student::GENDER_FEMALE,
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '9876500001',
            'guardian_email' => strtolower('guardian-'.$suffix).'@example.test',
            'address' => 'Private address '.$suffix,
            'admission_date' => now()->subMonth()->toDateString(),
            'status' => Student::STATUS_ACTIVE,
        ]));
    }

    /**
     * @return array{year: AcademicYear, class: SchoolClass, section: Section}
     */
    private function structure(School $school, string $suffix, bool $current = true): array
    {
        return $this->tenant($school, function () use ($suffix, $current): array {
            $year = AcademicYear::create([
                'name' => '2026-'.$suffix,
                'start_date' => now()->subMonths(2)->toDateString(),
                'end_date' => now()->addMonths(8)->toDateString(),
                'is_current' => $current,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'sort_order' => 1,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $class->id,
                'name' => 'Section '.$suffix,
                'capacity' => 30,
                'status' => Section::STATUS_ACTIVE,
            ]);

            return compact('year', 'class', 'section');
        });
    }

    /**
     * @param  array{year: AcademicYear, class: SchoolClass, section: Section}  $structure
     */
    private function enrollment(
        School $school,
        Student $student,
        array $structure,
        string $rollNo,
    ): StudentEnrollment {
        return $this->tenant($school, fn () => StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'roll_no' => $rollNo,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]));
    }

    /**
     * @param  array{year: AcademicYear, class: SchoolClass, section: Section}  $structure
     * @return array<string, mixed>
     */
    private function enrollmentPayload(array $structure): array
    {
        return [
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'roll_no' => 'ROLL-ONE',
            'enrollment_date' => now()->toDateString(),
        ];
    }

    private function enrollmentStatus(StudentEnrollment $enrollment, string $status): void
    {
        $enrollment->forceFill(['status' => $status])->save();
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
