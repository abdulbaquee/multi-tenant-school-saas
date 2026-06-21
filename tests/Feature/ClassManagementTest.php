<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SchoolClassService;
use App\Services\SecurityLogService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ClassManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_every_class_workflow(): void
    {
        $school = $this->school('One');
        $schoolClass = $this->schoolClass($school, ['status' => SchoolClass::STATUS_INACTIVE]);
        $this->archiveDirectly($school, $schoolClass);

        foreach ([
            fn () => $this->get(route('classes.index')),
            fn () => $this->get(route('classes.create')),
            fn () => $this->post(route('classes.store'), $this->classPayload()),
            fn () => $this->get(route('classes.show', $schoolClass)),
            fn () => $this->get(route('classes.edit', $schoolClass)),
            fn () => $this->put(route('classes.update', $schoolClass), $this->classPayload()),
            fn () => $this->patch(route('classes.activate', $schoolClass)),
            fn () => $this->patch(route('classes.deactivate', $schoolClass)),
            fn () => $this->patch(route('classes.archive', $schoolClass)),
            fn () => $this->patch(route('classes.restore', $schoolClass)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_reads_current_and_archived_classes_across_schools_but_cannot_mutate(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $classA = $this->schoolClass($schoolA, ['name' => 'Class A', 'code' => 'A']);
        $classB = $this->schoolClass($schoolB, ['name' => 'Class B', 'code' => 'B', 'status' => SchoolClass::STATUS_INACTIVE]);
        $this->archiveDirectly($schoolB, $classB);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get(route('classes.index'))
            ->assertOk()->assertSee('Class A')->assertSee('Class B')
            ->assertSee($schoolA->name)->assertSee($schoolB->name)
            ->assertSee('Archived')->assertDontSee('New Class');
        $this->actingAs($superAdmin)->get(route('classes.show', $classB))->assertOk()->assertSee('Archived');
        $this->actingAs($superAdmin)->get(route('classes.create'))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('classes.store'), $this->classPayload())->assertForbidden();
        $this->actingAs($superAdmin)->get(route('classes.edit', $classA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('classes.deactivate', $classA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('classes.restore', $classB))->assertForbidden();
    }

    public function test_school_admin_creates_normalized_tenant_owned_class_with_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->post(route('classes.store'), [
            'name' => '  Class 8  ', 'code' => '  viii  ', 'sort_order' => 8,
            'school_id' => 999, 'status' => 'inactive', 'deleted_at' => now(),
        ])->assertSessionHasErrors(['school_id', 'status', 'deleted_at']);

        $response = $this->actingAs($admin)->post(route('classes.store'), [
            'name' => '  Class 8  ', 'code' => '  viii  ', 'sort_order' => 8,
        ]);
        $classId = (int) DB::table('classes')->where('code', 'VIII')->value('id');

        $response->assertRedirect(route('classes.show', $classId));
        $this->assertDatabaseHas('classes', [
            'id' => $classId, 'school_id' => $school->id, 'name' => 'Class 8',
            'code' => 'VIII', 'sort_order' => 8, 'status' => 'active', 'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id, 'user_id' => $admin->id,
            'module' => 'academic_structure', 'action' => 'created',
            'subject_type' => SchoolClass::class, 'subject_id' => $classId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id, 'auditable_type' => SchoolClass::class,
            'auditable_id' => $classId, 'event' => 'created',
        ]);
    }

    public function test_class_validation_and_archived_identity_reservation_prevent_partial_writes(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $existing = $this->schoolClass($school, [
            'name' => 'Class 8', 'code' => 'VIII', 'status' => SchoolClass::STATUS_INACTIVE,
        ]);
        $this->archiveDirectly($school, $existing);

        $this->actingAs($admin)->post(route('classes.store'), [
            'name' => 'Class 8', 'code' => 'NEW', 'sort_order' => -1,
        ])->assertSessionHasErrors(['name', 'sort_order']);
        $this->actingAs($admin)->post(route('classes.store'), [
            'name' => 'Class 9', 'code' => 'viii', 'sort_order' => 65536,
        ])->assertSessionHasErrors(['code', 'sort_order']);

        $this->assertDatabaseCount('classes', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_school_admin_updates_class_fields_without_changing_tenant_or_lifecycle(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $schoolClass = $this->schoolClass($school);

        $this->actingAs($admin)->put(route('classes.update', $schoolClass), [
            ...$this->classPayload(), 'school_id' => 999, 'status' => 'inactive', 'deleted_at' => now(),
        ])->assertSessionHasErrors(['school_id', 'status', 'deleted_at']);

        $this->actingAs($admin)->put(route('classes.update', $schoolClass), [
            'name' => '  Class Eight  ', 'code' => '  c8  ', 'sort_order' => 80,
        ])->assertRedirect(route('classes.show', $schoolClass));

        $this->assertDatabaseHas('classes', [
            'id' => $schoolClass->id, 'school_id' => $school->id,
            'name' => 'Class Eight', 'code' => 'C8', 'sort_order' => 80, 'status' => 'active',
        ]);
    }

    public function test_teacher_sees_only_own_active_assigned_classes_and_rows(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $otherUser = $this->schoolUser(Role::TEACHER, $school, 'other@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $otherTeacher = $this->teacher($school, $otherUser, 'T-2');
        $assigned = $this->schoolClass($school, ['name' => 'Assigned Class', 'code' => 'ASSIGNED']);
        $unassigned = $this->schoolClass($school, ['name' => 'Hidden Class', 'code' => 'HIDDEN']);
        $ownSection = $this->section($school, $assigned, $teacher, 'Own Section');
        $ownSubject = $this->subject($school, $assigned, $teacher, 'Own Subject', 'OWN');
        $this->section($school, $assigned, $otherTeacher, 'Other Section');
        $this->subject($school, $assigned, $otherTeacher, 'Other Subject', 'OTHER');

        $this->actingAs($teacherUser)->get(route('classes.index'))
            ->assertOk()->assertSee('Assigned Classes')->assertSee($assigned->name)
            ->assertDontSee($unassigned->name)->assertSee('>1</td>', false)
            ->assertDontSee('New Class')->assertDontSee('Academic Years')->assertDontSee('Teacher Profiles');

        $this->actingAs($teacherUser)->get(route('classes.show', $assigned))
            ->assertOk()->assertSee($ownSection->name)->assertSee($ownSubject->name)
            ->assertDontSee('Other Section')->assertDontSee('Other Subject')->assertDontSee('Edit');
        $this->actingAs($teacherUser)->get(route('classes.show', $unassigned))->assertNotFound();
        $this->actingAs($teacherUser)->get(route('classes.edit', $assigned))->assertForbidden();
        $this->actingAs($teacherUser)->patch(route('classes.deactivate', $assigned))->assertForbidden();
    }

    public function test_teacher_access_disappears_when_profile_assignment_or_class_is_not_active(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $schoolClass = $this->schoolClass($school);
        $section = $this->section($school, $schoolClass, $teacher, 'A');

        $this->actingAs($teacherUser)->get(route('classes.index'))->assertOk()->assertSee($schoolClass->name);

        $this->tenant($school, fn () => $section->update(['status' => Section::STATUS_INACTIVE]));
        $this->actingAs($teacherUser)->get(route('classes.index'))->assertOk()->assertDontSee($schoolClass->name);
        $this->actingAs($teacherUser)->get(route('classes.show', $schoolClass))->assertNotFound();

        $this->tenant($school, function () use ($section, $schoolClass): void {
            $section->update(['status' => Section::STATUS_ACTIVE]);
            $schoolClass->update(['status' => SchoolClass::STATUS_INACTIVE]);
        });
        $this->actingAs($teacherUser)->get(route('classes.index'))->assertOk()->assertDontSee($schoolClass->name);

        $this->tenant($school, function () use ($teacher, $schoolClass): void {
            $schoolClass->update(['status' => SchoolClass::STATUS_ACTIVE]);
            $teacher->update(['status' => Teacher::STATUS_INACTIVE]);
        });
        $this->actingAs($teacherUser)->get(route('classes.index'))->assertForbidden();
    }

    public function test_accountant_inactive_and_malformed_users_are_denied_class_access(): void
    {
        $school = $this->school('One');
        $schoolClass = $this->schoolClass($school);
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $this->actingAs($accountant)->get(route('classes.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('classes.show', $schoolClass))->assertForbidden();

        $inactive = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'inactive@example.com');
        $inactive->update(['status' => User::STATUS_INACTIVE]);
        $this->actingAs($inactive)->get(route('classes.index'))->assertRedirect(route('login'));

        $malformed = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'malformed@example.com');
        $malformed->forceFill(['school_id' => null])->save();
        $this->actingAs($malformed)->get(route('classes.index'))->assertRedirect(route('login'));
    }

    public function test_active_sections_or_subjects_block_class_deactivation_and_archive(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $schoolClass = $this->schoolClass($school);
        $section = $this->section($school, $schoolClass, null, 'A');
        $subject = $this->subject($school, $schoolClass, null, 'Math', 'MATH');

        $this->actingAs($admin)->patch(route('classes.deactivate', $schoolClass))->assertSessionHasErrors('status');
        $this->tenant($school, fn () => $schoolClass->update(['status' => SchoolClass::STATUS_INACTIVE]));
        $this->actingAs($admin)->patch(route('classes.archive', $schoolClass))->assertSessionHasErrors('status');

        $this->tenant($school, function () use ($schoolClass, $section, $subject): void {
            $schoolClass->update(['status' => SchoolClass::STATUS_ACTIVE]);
            $section->update(['status' => Section::STATUS_INACTIVE]);
            $subject->update(['status' => Subject::STATUS_INACTIVE]);
        });
        $this->actingAs($admin)->patch(route('classes.deactivate', $schoolClass))->assertRedirect();
        $this->assertDatabaseHas('classes', ['id' => $schoolClass->id, 'status' => 'inactive']);
    }

    public function test_dependency_safe_class_archives_restores_inactive_and_activates(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $schoolClass = $this->schoolClass($school, ['status' => SchoolClass::STATUS_INACTIVE]);

        $this->actingAs($admin)->patch(route('classes.archive', $schoolClass))->assertRedirect(route('classes.show', $schoolClass));
        $this->assertSoftDeleted('classes', ['id' => $schoolClass->id]);
        $this->actingAs($admin)->get(route('classes.show', $schoolClass))->assertOk()->assertSee('Archived');
        $this->actingAs($admin)->patch(route('classes.restore', $schoolClass))->assertRedirect();
        $this->assertDatabaseHas('classes', ['id' => $schoolClass->id, 'status' => 'inactive', 'deleted_at' => null]);
        $this->actingAs($admin)->patch(route('classes.activate', $schoolClass))->assertRedirect();
        $this->assertDatabaseHas('classes', ['id' => $schoolClass->id, 'status' => 'active']);

        foreach (['archived', 'restored', 'activated'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'school_id' => $school->id, 'module' => 'academic_structure',
                'action' => $action, 'subject_type' => SchoolClass::class, 'subject_id' => $schoolClass->id,
            ]);
        }
    }

    public function test_cross_tenant_current_and_archived_classes_are_not_exposed(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $classA = $this->schoolClass($schoolA, ['name' => 'Visible', 'code' => 'VISIBLE']);
        $classB = $this->schoolClass($schoolB, ['name' => 'Hidden', 'code' => 'HIDDEN', 'status' => 'inactive']);
        $this->archiveDirectly($schoolB, $classB);

        $this->actingAs($adminA)->get(route('classes.index'))->assertOk()->assertSee($classA->name)->assertDontSee($classB->name);
        $this->actingAs($adminA)->get(route('classes.show', $classB))->assertNotFound();
        $this->actingAs($adminA)->put(route('classes.update', $classB), $this->classPayload())->assertNotFound();
        $this->actingAs($adminA)->patch(route('classes.restore', $classB))->assertNotFound();
    }

    public function test_exact_permissions_control_class_routes_and_controls_immediately(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $schoolClass = $this->schoolClass($school);
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $view = Permission::query()->where('code', 'academic.view')->firstOrFail();
        $role->permissions()->detach($view);
        $this->actingAs($admin)->get(route('classes.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('academic-years.index'), false);

        $role->permissions()->attach($view);
        $create = Permission::query()->where('code', 'academic.create')->firstOrFail();
        $role->permissions()->detach($create);
        $this->actingAs($admin)->get(route('classes.create'))->assertForbidden();

        $role->permissions()->attach($create);
        $update = Permission::query()->where('code', 'academic.update')->firstOrFail();
        $role->permissions()->detach($update);
        $this->actingAs($admin)->get(route('classes.edit', $schoolClass))->assertForbidden();

        $role->permissions()->attach($update);
        $delete = Permission::query()->where('code', 'academic.delete')->firstOrFail();
        $role->permissions()->detach($delete);
        $this->actingAs($admin)->patch(route('classes.deactivate', $schoolClass))->assertForbidden();
    }

    public function test_direct_services_reject_wrong_context_and_platform_mutation(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $service = app(SchoolClassService::class);
        $context = app(TenantContext::class);

        foreach ([
            fn () => $service->listFor($admin, []),
            fn () => $context->runAsPlatform(fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolB->id, fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->listFor($superAdmin, [])),
            fn () => $context->runAsPlatform(fn () => $service->create($this->classPayload(), $superAdmin)),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_logging_failures_roll_back_class_creation_and_archive(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Audit failed.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(SchoolClassService::class);

        try {
            $this->tenant($school, fn () => $service->create($this->classPayload(), $admin));
            $this->fail('Audit failure should escape.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit failed.', $exception->getMessage());
        }
        $this->assertDatabaseCount('classes', 0);

        $schoolClass = $this->schoolClass($school, ['status' => 'inactive']);
        $archiveLogger = Mockery::mock(SecurityLogService::class);
        $archiveLogger->shouldReceive('activity')->once()->andThrow(new RuntimeException('Activity failed.'));
        $this->app->instance(SecurityLogService::class, $archiveLogger);
        $archiveService = app(SchoolClassService::class);

        try {
            $this->tenant($school, fn () => $archiveService->archive($schoolClass, $admin));
            $this->fail('Activity failure should escape.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Activity failed.', $exception->getMessage());
        }
        $this->assertNotSoftDeleted('classes', ['id' => $schoolClass->id]);
    }

    public function test_class_navigation_and_retention_routes_match_contract(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $schoolClass = $this->schoolClass($school);

        $this->actingAs($admin)->get(route('classes.show', $schoolClass))
            ->assertOk()->assertSee('Academic Structure')->assertSee('Classes')->assertSee('aria-current="page"', false);
        $this->actingAs($admin)->delete('/classes/'.$schoolClass->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('classes', ['id' => $schoolClass->id, 'deleted_at' => null]);
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix, 'code' => 'SCH-'.strtoupper($suffix),
            'email' => 'school-'.$key.'@example.com', 'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'email' => $email, 'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function teacher(School $school, User $user, string $code): Teacher
    {
        return $this->tenant($school, fn () => Teacher::create([
            'user_id' => $user->id, 'employee_code' => $code, 'status' => Teacher::STATUS_ACTIVE,
        ]));
    }

    private function schoolClass(School $school, array $attributes = []): SchoolClass
    {
        return $this->tenant($school, fn () => SchoolClass::create([
            'name' => 'Class 8', 'code' => 'VIII', 'sort_order' => 8,
            'status' => SchoolClass::STATUS_ACTIVE, ...$attributes,
        ]));
    }

    private function section(School $school, SchoolClass $class, ?Teacher $teacher, string $name): Section
    {
        return $this->tenant($school, fn () => Section::create([
            'class_id' => $class->id, 'teacher_id' => $teacher?->id,
            'name' => $name, 'capacity' => 40, 'status' => Section::STATUS_ACTIVE,
        ]));
    }

    private function subject(School $school, SchoolClass $class, ?Teacher $teacher, string $name, string $code): Subject
    {
        return $this->tenant($school, fn () => Subject::create([
            'class_id' => $class->id, 'teacher_id' => $teacher?->id,
            'name' => $name, 'code' => $code, 'subject_type' => Subject::TYPE_THEORY,
            'status' => Subject::STATUS_ACTIVE,
        ]));
    }

    private function archiveDirectly(School $school, SchoolClass $schoolClass): void
    {
        $this->tenant($school, fn () => $schoolClass->delete());
    }

    private function superAdmin(): User
    {
        return User::query()->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))->firstOrFail();
    }

    private function classPayload(): array
    {
        return ['name' => 'Class 9', 'code' => 'IX', 'sort_order' => 9];
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
