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
use App\Services\SecurityLogService;
use App\Services\TeacherService;
use App\Services\UserService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TeacherProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_from_every_teacher_profile_workflow(): void
    {
        $school = $this->school('One');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $teacher = $this->teacher($school, $user);
        $this->archiveDirectly($school, $teacher);

        $requests = [
            fn () => $this->get(route('teacher-profiles.index')),
            fn () => $this->get(route('teacher-profiles.create')),
            fn () => $this->post(route('teacher-profiles.store'), $this->profilePayload($user)),
            fn () => $this->get(route('teacher-profiles.show', $teacher)),
            fn () => $this->get(route('teacher-profiles.edit', $teacher)),
            fn () => $this->put(route('teacher-profiles.update', $teacher), $this->profileUpdatePayload()),
            fn () => $this->patch(route('teacher-profiles.activate', $teacher)),
            fn () => $this->patch(route('teacher-profiles.deactivate', $teacher)),
            fn () => $this->patch(route('teacher-profiles.archive', $teacher)),
            fn () => $this->patch(route('teacher-profiles.restore', $teacher)),
        ];

        foreach ($requests as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_can_read_current_and_archived_profiles_across_schools_but_cannot_mutate(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $userA = $this->teacherUser($schoolA, 'teacher-a@example.com', 'Teacher A');
        $userB = $this->teacherUser($schoolB, 'teacher-b@example.com', 'Teacher B');
        $teacherA = $this->teacher($schoolA, $userA, ['employee_code' => 'A-001']);
        $teacherB = $this->teacher($schoolB, $userB, ['employee_code' => 'B-001', 'status' => Teacher::STATUS_INACTIVE]);
        $this->archiveDirectly($schoolB, $teacherB);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->get(route('teacher-profiles.index'))
            ->assertOk()
            ->assertSee('Teacher A')
            ->assertSee('Teacher B')
            ->assertSee($schoolA->name)
            ->assertSee($schoolB->name)
            ->assertSee('Archived')
            ->assertDontSee('New Teacher Profile');

        $this->actingAs($superAdmin)->get(route('teacher-profiles.show', $teacherB))->assertOk()->assertSee('Archived');
        $this->actingAs($superAdmin)->get(route('teacher-profiles.create'))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('teacher-profiles.store'), $this->profilePayload($userA))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('teacher-profiles.edit', $teacherA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('teacher-profiles.deactivate', $teacherA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('teacher-profiles.restore', $teacherB))->assertForbidden();
    }

    public function test_teacher_accountant_inactive_and_malformed_users_cannot_administer_teacher_profiles(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->teacherUser($school, 'linked@example.com');
        $profile = $this->teacher($school, $teacherUser);

        foreach ([Role::TEACHER, Role::ACCOUNTANT] as $index => $roleCode) {
            $actor = $this->schoolUser($roleCode, $school, $roleCode.$index.'@example.com');

            $this->actingAs($actor)->get(route('teacher-profiles.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('teacher-profiles.show', $profile))->assertForbidden();
            $this->actingAs($actor)->get(route('dashboard'))->assertDontSee(route('teacher-profiles.index'), false);
        }

        $inactive = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'inactive-admin@example.com');
        $inactive->update(['status' => User::STATUS_INACTIVE]);
        $this->actingAs($inactive)->get(route('teacher-profiles.index'))->assertRedirect(route('login'));

        $malformed = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'malformed@example.com');
        $malformed->forceFill(['school_id' => null])->save();
        $this->actingAs($malformed)->get(route('teacher-profiles.index'))->assertRedirect(route('login'));
    }

    public function test_create_form_lists_only_active_same_school_unlinked_teacher_users(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $eligible = $this->teacherUser($schoolA, 'eligible@example.com', 'Eligible Teacher');
        $linked = $this->teacherUser($schoolA, 'linked@example.com', 'Linked Teacher');
        $archivedLinked = $this->teacherUser($schoolA, 'archived@example.com', 'Archived Linked');
        $inactive = $this->teacherUser($schoolA, 'inactive@example.com', 'Inactive Teacher');
        $inactive->update(['status' => User::STATUS_INACTIVE]);
        $deleted = $this->teacherUser($schoolA, 'deleted@example.com', 'Deleted Teacher');
        $deleted->delete();
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $schoolA, 'accountant@example.com');
        $otherSchool = $this->teacherUser($schoolB, 'other@example.com', 'Other School Teacher');
        $this->teacher($schoolA, $linked, ['employee_code' => 'LINKED-1']);
        $archivedProfile = $this->teacher($schoolA, $archivedLinked, [
            'employee_code' => 'ARCH-1',
            'status' => Teacher::STATUS_INACTIVE,
        ]);
        $this->archiveDirectly($schoolA, $archivedProfile);

        $response = $this->actingAs($admin)->get(route('teacher-profiles.create'));

        $response->assertOk()->assertSee($eligible->email);
        foreach ([$linked, $archivedLinked, $inactive, $deleted, $accountant, $otherSchool] as $hidden) {
            $response->assertDontSee($hidden->email);
        }
    }

    public function test_school_admin_can_create_a_normalized_tenant_owned_profile_with_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com', 'Anita Sharma');

        $this->actingAs($admin)->post(route('teacher-profiles.store'), [
            ...$this->profilePayload($user),
            'employee_code' => '  tch-001  ',
            'qualification' => '  M.Ed.  ',
            'specialization' => '  Mathematics  ',
            'phone' => '  9876500011  ',
            'school_id' => 999,
            'status' => Teacher::STATUS_INACTIVE,
            'deleted_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors(['school_id', 'status', 'deleted_at']);

        $response = $this->actingAs($admin)->post(route('teacher-profiles.store'), [
            ...$this->profilePayload($user),
            'employee_code' => '  tch-001  ',
            'qualification' => '  M.Ed.  ',
            'specialization' => '  Mathematics  ',
            'phone' => '  9876500011  ',
        ]);

        $teacherId = (int) DB::table('teachers')->where('employee_code', 'TCH-001')->value('id');
        $response->assertRedirect(route('teacher-profiles.show', $teacherId));
        $this->assertDatabaseHas('teachers', [
            'id' => $teacherId,
            'school_id' => $school->id,
            'user_id' => $user->id,
            'employee_code' => 'TCH-001',
            'qualification' => 'M.Ed.',
            'specialization' => 'Mathematics',
            'phone' => '9876500011',
            'status' => Teacher::STATUS_ACTIVE,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'module' => 'academic_structure',
            'action' => 'created',
            'subject_type' => Teacher::class,
            'subject_id' => $teacherId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'auditable_type' => Teacher::class,
            'auditable_id' => $teacherId,
            'event' => 'created',
        ]);
    }

    public function test_wrong_role_inactive_deleted_cross_school_and_retained_users_are_rejected(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $wrongRole = $this->schoolUser(Role::ACCOUNTANT, $schoolA, 'wrong@example.com');
        $inactive = $this->teacherUser($schoolA, 'inactive@example.com');
        $inactive->update(['status' => User::STATUS_INACTIVE]);
        $deleted = $this->teacherUser($schoolA, 'deleted@example.com');
        $deleted->delete();
        $crossSchool = $this->teacherUser($schoolB, 'cross@example.com');
        $linked = $this->teacherUser($schoolA, 'linked@example.com');
        $linkedProfile = $this->teacher($schoolA, $linked, [
            'employee_code' => 'RETAINED-1',
            'status' => Teacher::STATUS_INACTIVE,
        ]);
        $this->archiveDirectly($schoolA, $linkedProfile);

        foreach ([$wrongRole, $inactive, $deleted, $crossSchool, $linked] as $invalidUser) {
            $this->actingAs($admin)
                ->post(route('teacher-profiles.store'), $this->profilePayload($invalidUser, 'TRY-'.$invalidUser->id))
                ->assertSessionHasErrors('user_id');
        }

        $this->assertDatabaseCount('teachers', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_employee_code_remains_reserved_after_archive(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $firstUser = $this->teacherUser($school, 'first@example.com');
        $secondUser = $this->teacherUser($school, 'second@example.com');
        $profile = $this->teacher($school, $firstUser, [
            'employee_code' => 'TCH-RESERVED',
            'status' => Teacher::STATUS_INACTIVE,
        ]);
        $this->archiveDirectly($school, $profile);

        $this->actingAs($admin)
            ->post(route('teacher-profiles.store'), $this->profilePayload($secondUser, 'tch-reserved'))
            ->assertSessionHasErrors('employee_code');

        $this->assertDatabaseCount('teachers', 1);
    }

    public function test_school_admin_can_update_profile_fields_but_not_linked_identity_or_lifecycle_fields(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $otherUser = $this->teacherUser($school, 'other@example.com');
        $teacher = $this->teacher($school, $user);

        $this->actingAs($admin)->put(route('teacher-profiles.update', $teacher), [
            ...$this->profileUpdatePayload(),
            'user_id' => $otherUser->id,
            'school_id' => 999,
            'status' => Teacher::STATUS_INACTIVE,
            'deleted_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors(['user_id', 'school_id', 'status', 'deleted_at']);

        $response = $this->actingAs($admin)->put(route('teacher-profiles.update', $teacher), [
            'employee_code' => 'tch-002',
            'qualification' => 'M.Sc., B.Ed.',
            'specialization' => 'Physics',
            'phone' => '9876500022',
            'joining_date' => '2024-06-15',
        ]);

        $response->assertRedirect(route('teacher-profiles.show', $teacher));
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'school_id' => $school->id,
            'user_id' => $user->id,
            'employee_code' => 'TCH-002',
            'qualification' => 'M.Sc., B.Ed.',
            'specialization' => 'Physics',
            'phone' => '9876500022',
        ]);
    }

    public function test_active_section_or_subject_assignments_block_profile_deactivation(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $teacher = $this->teacher($school, $user);
        [$section, $subject] = $this->assignments($school, $teacher);

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.deactivate', $teacher))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => Teacher::STATUS_ACTIVE]);

        $this->tenant($school, function () use ($teacher): void {
            $teacher->update(['status' => Teacher::STATUS_INACTIVE]);
        });
        $this->actingAs($admin)
            ->patch(route('teacher-profiles.archive', $teacher))
            ->assertSessionHasErrors('status');
        $this->assertNotSoftDeleted('teachers', ['id' => $teacher->id]);

        $this->tenant($school, function () use ($teacher, $section, $subject): void {
            $teacher->update(['status' => Teacher::STATUS_ACTIVE]);
            $section->update(['status' => Section::STATUS_INACTIVE]);
            $subject->update(['status' => Subject::STATUS_INACTIVE]);
        });

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.deactivate', $teacher))
            ->assertRedirect(route('teacher-profiles.show', $teacher));
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => Teacher::STATUS_INACTIVE]);
    }

    public function test_inactive_dependency_safe_profile_can_archive_restore_inactive_and_activate(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $teacher = $this->teacher($school, $user, ['status' => Teacher::STATUS_INACTIVE]);

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.archive', $teacher))
            ->assertRedirect(route('teacher-profiles.show', $teacher));
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
        $this->actingAs($admin)->get(route('teacher-profiles.show', $teacher))->assertOk()->assertSee('Archived');

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.restore', $teacher))
            ->assertRedirect(route('teacher-profiles.show', $teacher));
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'status' => Teacher::STATUS_INACTIVE,
            'deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.activate', $teacher))
            ->assertRedirect(route('teacher-profiles.show', $teacher));
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => Teacher::STATUS_ACTIVE]);
        foreach (['archived', 'restored', 'activated'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'school_id' => $school->id,
                'module' => 'academic_structure',
                'action' => $action,
                'subject_type' => Teacher::class,
                'subject_id' => $teacher->id,
            ]);
        }
    }

    public function test_restore_and_activation_revalidate_the_linked_teacher_user(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $teacher = $this->teacher($school, $user, ['status' => Teacher::STATUS_INACTIVE]);
        $this->archiveDirectly($school, $teacher);
        DB::table('users')->where('id', $user->id)->update(['status' => User::STATUS_INACTIVE]);

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.restore', $teacher))
            ->assertSessionHasErrors('user_id');
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);

        DB::table('users')->where('id', $user->id)->update(['status' => User::STATUS_ACTIVE]);
        $this->actingAs($admin)->patch(route('teacher-profiles.restore', $teacher))->assertRedirect();
        DB::table('users')->where('id', $user->id)->update(['status' => User::STATUS_INACTIVE]);

        $this->actingAs($admin)
            ->patch(route('teacher-profiles.activate', $teacher))
            ->assertSessionHasErrors('user_id');
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'status' => Teacher::STATUS_INACTIVE]);
    }

    public function test_cross_tenant_current_and_archived_profiles_cannot_be_listed_bound_or_mutated(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $profileA = $this->teacher($schoolA, $this->teacherUser($schoolA, 'a@example.com', 'Visible Teacher'), ['employee_code' => 'A-1']);
        $profileB = $this->teacher($schoolB, $this->teacherUser($schoolB, 'b@example.com', 'Hidden Teacher'), [
            'employee_code' => 'B-1',
            'status' => Teacher::STATUS_INACTIVE,
        ]);
        $this->archiveDirectly($schoolB, $profileB);

        $this->actingAs($adminA)
            ->get(route('teacher-profiles.index'))
            ->assertOk()
            ->assertSee($profileA->user->name)
            ->assertDontSee($profileB->user->name);
        $this->actingAs($adminA)->get(route('teacher-profiles.show', $profileB))->assertNotFound();
        $this->actingAs($adminA)->put(route('teacher-profiles.update', $profileB), $this->profileUpdatePayload())->assertNotFound();
        $this->actingAs($adminA)->patch(route('teacher-profiles.restore', $profileB))->assertNotFound();
    }

    public function test_exact_permissions_control_profile_routes_actions_and_navigation_immediately(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $teacher = $this->teacher($school, $user);
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $this->actingAs($admin)->get(route('teacher-profiles.index'))->assertOk()->assertSee('New Teacher Profile');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee(route('academic-years.index'), false);

        $view = Permission::query()->where('code', 'academic.view')->firstOrFail();
        $role->permissions()->detach($view);
        $this->actingAs($admin)->get(route('teacher-profiles.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('academic-years.index'), false);

        $role->permissions()->attach($view);
        $create = Permission::query()->where('code', 'academic.create')->firstOrFail();
        $role->permissions()->detach($create);
        $this->actingAs($admin)->get(route('teacher-profiles.create'))->assertForbidden();

        $role->permissions()->attach($create);
        $update = Permission::query()->where('code', 'academic.update')->firstOrFail();
        $role->permissions()->detach($update);
        $this->actingAs($admin)->get(route('teacher-profiles.edit', $teacher))->assertForbidden();

        $role->permissions()->attach($update);
        $delete = Permission::query()->where('code', 'academic.delete')->firstOrFail();
        $role->permissions()->detach($delete);
        $this->actingAs($admin)->patch(route('teacher-profiles.deactivate', $teacher))->assertForbidden();
    }

    public function test_direct_service_calls_reject_unresolved_wrong_tenant_and_platform_mutation(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $user = $this->teacherUser($schoolA, 'teacher@example.com');
        $service = app(TeacherService::class);
        $context = app(TenantContext::class);

        foreach ([
            fn () => $service->listFor($admin, []),
            fn () => $context->runAsPlatform(fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolB->id, fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->listFor($superAdmin, [])),
            fn () => $context->runAsPlatform(fn () => $service->create($this->profilePayload($user), $superAdmin)),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_logging_failures_roll_back_profile_creation_and_archive(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $user = $this->teacherUser($school, 'teacher@example.com');
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced audit failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(TeacherService::class);

        try {
            app(TenantContext::class)->runAsTenant(
                $school->id,
                fn () => $service->create($this->profilePayload($user), $admin),
            );
            $this->fail('The forced audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        }
        $this->assertDatabaseCount('teachers', 0);

        $teacher = $this->teacher($school, $user, ['status' => Teacher::STATUS_INACTIVE]);
        $archiveLogger = Mockery::mock(SecurityLogService::class);
        $archiveLogger->shouldReceive('activity')->once()->andThrow(new RuntimeException('Forced activity failure.'));
        $this->app->instance(SecurityLogService::class, $archiveLogger);
        $archiveService = app(TeacherService::class);

        try {
            app(TenantContext::class)->runAsTenant(
                $school->id,
                fn () => $archiveService->archive($teacher, $admin),
            );
            $this->fail('The forced activity failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced activity failure.', $exception->getMessage());
        }
        $this->assertNotSoftDeleted('teachers', ['id' => $teacher->id]);
    }

    public function test_archived_profiles_still_block_user_role_and_school_changes(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $user = $this->teacherUser($schoolA, 'teacher@example.com');
        $teacher = $this->teacher($schoolA, $user, ['status' => Teacher::STATUS_INACTIVE]);
        $this->archiveDirectly($schoolA, $teacher);

        $service = app(UserService::class);
        $superAdmin = $this->superAdmin();

        try {
            app(TenantContext::class)->runAsPlatform(fn () => $service->update($user, [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => null,
                'role_id' => Role::query()->where('code', Role::ACCOUNTANT)->value('id'),
                'school_id' => $schoolB->id,
                'password' => null,
            ], $superAdmin));
            $this->fail('Retained Teacher Profile should block role and school changes.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role_id', $exception->errors());
            $this->assertArrayHasKey('school_id', $exception->errors());
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'school_id' => $schoolA->id,
            'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
        ]);
    }

    public function test_navigation_state_and_retention_routes_match_the_contract(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->teacher($school, $this->teacherUser($school, 'teacher@example.com'));

        $this->actingAs($admin)
            ->get(route('teacher-profiles.show', $teacher))
            ->assertOk()
            ->assertSee('Academic Structure')
            ->assertSee('Teacher Profiles')
            ->assertSee('aria-current="page"', false);

        $this->actingAs($admin)->delete('/teacher-profiles/'.$teacher->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'deleted_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function school(string $suffix, array $attributes = []): School
    {
        $key = strtolower(str_replace(' ', '-', $suffix));

        return School::create([
            'name' => 'School '.$suffix,
            'code' => strtoupper('SCH-'.$key),
            'email' => 'school-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
            ...$attributes,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function teacherUser(School $school, string $email, string $name = 'Teacher User'): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
            'name' => $name,
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function teacher(School $school, User $user, array $attributes = []): Teacher
    {
        return $this->tenant($school, fn () => Teacher::create([
            'user_id' => $user->id,
            'employee_code' => 'TCH-'.$user->id,
            'qualification' => 'B.Ed.',
            'specialization' => 'General Studies',
            'phone' => null,
            'joining_date' => '2025-06-01',
            'status' => Teacher::STATUS_ACTIVE,
            ...$attributes,
        ]));
    }

    /**
     * @return array{Section, Subject}
     */
    private function assignments(School $school, Teacher $teacher): array
    {
        return $this->tenant($school, function () use ($teacher): array {
            $schoolClass = SchoolClass::create([
                'name' => 'Class 8',
                'code' => 'VIII',
                'sort_order' => 8,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'A',
                'capacity' => 40,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $subject = Subject::create([
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'Mathematics',
                'code' => 'MATH-8',
                'subject_type' => Subject::TYPE_THEORY,
                'status' => Subject::STATUS_ACTIVE,
            ]);

            return [$section, $subject];
        });
    }

    private function archiveDirectly(School $school, Teacher $teacher): void
    {
        $this->tenant($school, function () use ($teacher): void {
            $teacher->delete();
        });
    }

    private function superAdmin(): User
    {
        return User::query()
            ->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, ?string $employeeCode = null): array
    {
        return [
            'user_id' => $user->id,
            'employee_code' => $employeeCode ?? 'TCH-NEW',
            'qualification' => 'B.Ed.',
            'specialization' => 'Mathematics',
            'phone' => '9876500001',
            'joining_date' => '2025-06-01',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profileUpdatePayload(): array
    {
        return [
            'employee_code' => 'TCH-UPDATED',
            'qualification' => 'M.Ed.',
            'specialization' => 'Science',
            'phone' => '9876500002',
            'joining_date' => '2024-06-01',
        ];
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
