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
use App\Services\SectionService;
use App\Services\SecurityLogService;
use App\Services\SubjectService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SectionSubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_section_and_subject_workflows(): void
    {
        $school = $this->school('One');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, null, ['status' => Section::STATUS_INACTIVE]);
        $subject = $this->subject($school, $class, null, ['status' => Subject::STATUS_INACTIVE]);
        $this->tenant($school, function () use ($section, $subject): void {
            $section->delete();
            $subject->delete();
        });

        foreach ([
            fn () => $this->get(route('sections.index')),
            fn () => $this->post(route('sections.store'), []),
            fn () => $this->get(route('sections.show', $section)),
            fn () => $this->patch(route('sections.restore', $section)),
            fn () => $this->get(route('subjects.index')),
            fn () => $this->post(route('subjects.store'), []),
            fn () => $this->get(route('subjects.show', $subject)),
            fn () => $this->patch(route('subjects.restore', $subject)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_reads_current_and_archived_records_across_schools_but_cannot_mutate(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $classA = $this->schoolClass($schoolA, ['name' => 'Class A', 'code' => 'A']);
        $classB = $this->schoolClass($schoolB, ['name' => 'Class B', 'code' => 'B']);
        $sectionA = $this->section($schoolA, $classA, null, ['name' => 'Section A']);
        $subjectB = $this->subject($schoolB, $classB, null, ['name' => 'Science B', 'code' => 'SCI-B', 'status' => Subject::STATUS_INACTIVE]);
        $this->tenant($schoolB, fn () => $subjectB->delete());
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get(route('sections.index'))
            ->assertOk()->assertSee($sectionA->name)->assertSee($schoolA->name)->assertDontSee('New Section');
        $this->actingAs($superAdmin)->get(route('subjects.index'))
            ->assertOk()->assertSee($subjectB->name)->assertSee($schoolB->name)->assertSee('Archived')->assertDontSee('New Subject');
        $this->actingAs($superAdmin)->get(route('subjects.show', $subjectB))->assertOk();
        $this->actingAs($superAdmin)->get(route('sections.create'))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('subjects.store'), $this->subjectPayload($classA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('sections.deactivate', $sectionA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('subjects.restore', $subjectB))->assertForbidden();
    }

    public function test_school_admin_creates_normalized_tenant_owned_section_and_subject_with_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $class = $this->schoolClass($school);

        $this->actingAs($admin)->post(route('sections.store'), [
            ...$this->sectionPayload($class, $teacher), 'name' => '  A  ',
            'school_id' => 999, 'status' => 'inactive', 'deleted_at' => now(),
        ])->assertSessionHasErrors(['school_id', 'status', 'deleted_at']);

        $this->actingAs($admin)->post(route('sections.store'), [
            ...$this->sectionPayload($class, $teacher), 'name' => '  A  ',
        ])->assertRedirect();
        $sectionId = (int) DB::table('sections')->where('name', 'A')->value('id');

        $this->actingAs($admin)->post(route('subjects.store'), [
            ...$this->subjectPayload($class, $teacher), 'name' => '  Mathematics  ', 'code' => '  math  ',
        ])->assertRedirect();
        $subjectId = (int) DB::table('subjects')->where('code', 'MATH')->value('id');

        $this->assertDatabaseHas('sections', [
            'id' => $sectionId, 'school_id' => $school->id, 'class_id' => $class->id,
            'teacher_id' => $teacher->id, 'name' => 'A', 'capacity' => 40, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('subjects', [
            'id' => $subjectId, 'school_id' => $school->id, 'class_id' => $class->id,
            'teacher_id' => $teacher->id, 'name' => 'Mathematics', 'code' => 'MATH',
            'subject_type' => Subject::TYPE_THEORY, 'status' => 'active',
        ]);
        foreach ([[Section::class, $sectionId], [Subject::class, $subjectId]] as [$type, $id]) {
            $this->assertDatabaseHas('activity_logs', [
                'school_id' => $school->id, 'user_id' => $admin->id,
                'module' => 'academic_structure', 'action' => 'created',
                'subject_type' => $type, 'subject_id' => $id,
            ]);
            $this->assertDatabaseHas('audit_logs', [
                'school_id' => $school->id, 'auditable_type' => $type,
                'auditable_id' => $id, 'event' => 'created',
            ]);
        }
    }

    public function test_relationship_validation_rejects_cross_tenant_inactive_or_archived_parents_and_teachers(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $classA = $this->schoolClass($schoolA, ['name' => 'Class A', 'code' => 'A']);
        $classB = $this->schoolClass($schoolB, ['name' => 'Class B', 'code' => 'B']);
        $teacherBUser = $this->schoolUser(Role::TEACHER, $schoolB, 'teacher-b@example.com');
        $teacherB = $this->teacher($schoolB, $teacherBUser, 'T-B');

        $this->actingAs($admin)->post(route('sections.store'), $this->sectionPayload($classB, $teacherB))
            ->assertSessionHasErrors(['class_id', 'teacher_id']);
        $this->actingAs($admin)->post(route('subjects.store'), $this->subjectPayload($classB, $teacherB))
            ->assertSessionHasErrors(['class_id', 'teacher_id']);

        $this->tenant($schoolA, fn () => $classA->update(['status' => SchoolClass::STATUS_INACTIVE]));
        $this->actingAs($admin)->post(route('sections.store'), $this->sectionPayload($classA))
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseCount('sections', 0);
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_retained_uniqueness_capacity_and_subject_type_validation_match_schema(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, null, ['name' => 'A', 'status' => Section::STATUS_INACTIVE]);
        $subject = $this->subject($school, $class, null, ['code' => 'MATH', 'status' => Subject::STATUS_INACTIVE]);
        $this->tenant($school, function () use ($section, $subject): void {
            $section->delete();
            $subject->delete();
        });

        $this->actingAs($admin)->post(route('sections.store'), [
            ...$this->sectionPayload($class), 'name' => 'A', 'capacity' => 0,
        ])->assertSessionHasErrors(['name', 'capacity']);
        $this->actingAs($admin)->post(route('sections.store'), [
            ...$this->sectionPayload($class), 'name' => 'B', 'capacity' => 65536,
        ])->assertSessionHasErrors('capacity');
        $this->actingAs($admin)->post(route('subjects.store'), [
            ...$this->subjectPayload($class), 'code' => 'math', 'subject_type' => 'laboratory',
        ])->assertSessionHasErrors(['code', 'subject_type']);

        $otherClass = $this->schoolClass($school, ['name' => 'Class 9', 'code' => 'IX']);
        $this->actingAs($admin)->post(route('sections.store'), [
            ...$this->sectionPayload($otherClass), 'name' => 'A',
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('subjects.store'), [
            ...$this->subjectPayload($otherClass), 'code' => 'math',
        ])->assertRedirect();
    }

    public function test_school_admin_updates_relationships_and_can_clear_teacher_assignment(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $classA = $this->schoolClass($school);
        $classB = $this->schoolClass($school, ['name' => 'Class 9', 'code' => 'IX']);
        $section = $this->section($school, $classA, $teacher);
        $subject = $this->subject($school, $classA, $teacher);

        $this->actingAs($admin)->put(route('sections.update', $section), [
            ...$this->sectionPayload($classB), 'name' => ' B ', 'capacity' => '',
        ])->assertRedirect(route('sections.show', $section));
        $this->actingAs($admin)->put(route('subjects.update', $subject), [
            ...$this->subjectPayload($classB), 'teacher_id' => '', 'name' => ' Applied Science ',
            'code' => ' sci ', 'subject_type' => Subject::TYPE_PRACTICAL,
        ])->assertRedirect(route('subjects.show', $subject));

        $this->assertDatabaseHas('sections', [
            'id' => $section->id, 'class_id' => $classB->id, 'teacher_id' => null,
            'name' => 'B', 'capacity' => null, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id, 'class_id' => $classB->id, 'teacher_id' => null,
            'name' => 'Applied Science', 'code' => 'SCI', 'subject_type' => 'practical',
        ]);
    }

    public function test_teacher_sees_only_own_active_assignments_under_active_classes(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $otherUser = $this->schoolUser(Role::TEACHER, $school, 'other@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $other = $this->teacher($school, $otherUser, 'T-2');
        $class = $this->schoolClass($school);
        $ownSection = $this->section($school, $class, $teacher, ['name' => 'Own Section']);
        $ownSubject = $this->subject($school, $class, $teacher, ['name' => 'Own Subject', 'code' => 'OWN']);
        $otherSection = $this->section($school, $class, $other, ['name' => 'Hidden Section']);
        $otherSubject = $this->subject($school, $class, $other, ['name' => 'Hidden Subject', 'code' => 'HIDDEN']);

        $this->actingAs($teacherUser)->get(route('sections.index'))
            ->assertOk()->assertSee('Assigned Sections')->assertSee($ownSection->name)
            ->assertDontSee($otherSection->name)->assertDontSee('New Section');
        $this->actingAs($teacherUser)->get(route('subjects.index'))
            ->assertOk()->assertSee('Assigned Subjects')->assertSee($ownSubject->name)
            ->assertDontSee($otherSubject->name)->assertDontSee('New Subject');
        $this->actingAs($teacherUser)->get(route('sections.show', $ownSection))->assertOk()->assertDontSee('Edit');
        $this->actingAs($teacherUser)->get(route('subjects.show', $ownSubject))->assertOk()->assertDontSee('Edit');
        $this->actingAs($teacherUser)->get(route('sections.show', $otherSection))->assertNotFound();
        $this->actingAs($teacherUser)->get(route('subjects.show', $otherSubject))->assertNotFound();
        $this->actingAs($teacherUser)->patch(route('sections.deactivate', $ownSection))->assertForbidden();
        $this->actingAs($teacherUser)->patch(route('subjects.deactivate', $ownSubject))->assertForbidden();
    }

    public function test_teacher_visibility_disappears_when_profile_assignment_record_or_parent_is_inactive(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, $teacher, ['name' => 'Dormant Section']);
        $subject = $this->subject($school, $class, $teacher, ['name' => 'Dormant Subject', 'code' => 'DORMANT']);

        $this->tenant($school, function () use ($section, $subject): void {
            $section->update(['status' => Section::STATUS_INACTIVE]);
            $subject->update(['status' => Subject::STATUS_INACTIVE]);
        });
        $this->actingAs($teacherUser)->get(route('sections.index'))->assertOk()->assertDontSee($section->name);
        $this->actingAs($teacherUser)->get(route('subjects.index'))->assertOk()->assertDontSee($subject->name);

        $this->tenant($school, function () use ($section, $subject, $class): void {
            $section->update(['status' => Section::STATUS_ACTIVE]);
            $subject->update(['status' => Subject::STATUS_ACTIVE]);
            $class->update(['status' => SchoolClass::STATUS_INACTIVE]);
        });
        $this->actingAs($teacherUser)->get(route('sections.show', $section))->assertNotFound();
        $this->actingAs($teacherUser)->get(route('subjects.show', $subject))->assertNotFound();

        $this->tenant($school, function () use ($class): void {
            $class->forceFill(['status' => SchoolClass::STATUS_ACTIVE])->save();
            $class->delete();
        });
        $this->actingAs($teacherUser)->get(route('sections.index'))->assertOk()->assertDontSee($section->name);
        $this->actingAs($teacherUser)->get(route('subjects.index'))->assertOk()->assertDontSee($subject->name);

        $this->tenant($school, function () use ($class, $teacher): void {
            $class->restore();
            $teacher->update(['status' => Teacher::STATUS_INACTIVE]);
        });
        $this->actingAs($teacherUser)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('subjects.index'))->assertForbidden();
    }

    public function test_accountant_inactive_and_malformed_users_are_denied(): void
    {
        $school = $this->school('One');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class);
        $subject = $this->subject($school, $class);
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $this->actingAs($accountant)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('subjects.show', $subject))->assertForbidden();

        $inactive = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'inactive@example.com');
        $inactive->update(['status' => User::STATUS_INACTIVE]);
        $this->actingAs($inactive)->get(route('sections.show', $section))->assertRedirect(route('login'));

        $malformed = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'malformed@example.com');
        $malformed->forceFill(['school_id' => null])->save();
        $this->actingAs($malformed)->get(route('subjects.index'))->assertRedirect(route('login'));
    }

    public function test_lifecycle_archives_restores_inactive_and_revalidates_relationships(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, $teacher);
        $subject = $this->subject($school, $class, $teacher);

        $this->actingAs($admin)->patch(route('sections.deactivate', $section))->assertRedirect();
        $this->actingAs($admin)->patch(route('sections.archive', $section))->assertRedirect();
        $this->actingAs($admin)->patch(route('subjects.deactivate', $subject))->assertRedirect();
        $this->actingAs($admin)->patch(route('subjects.archive', $subject))->assertRedirect();
        $this->assertSoftDeleted('sections', ['id' => $section->id]);
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);

        $this->tenant($school, fn () => $class->update(['status' => SchoolClass::STATUS_INACTIVE]));
        $this->actingAs($admin)->patch(route('sections.restore', $section))->assertNotFound();
        $this->actingAs($admin)->patch(route('subjects.restore', $subject))->assertNotFound();

        $this->tenant($school, fn () => $class->update(['status' => SchoolClass::STATUS_ACTIVE]));
        $this->actingAs($admin)->patch(route('sections.restore', $section))->assertRedirect();
        $this->actingAs($admin)->patch(route('subjects.restore', $subject))->assertRedirect();
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'status' => 'inactive', 'deleted_at' => null]);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'status' => 'inactive', 'deleted_at' => null]);
        $this->actingAs($admin)->patch(route('sections.activate', $section))->assertRedirect();
        $this->actingAs($admin)->patch(route('subjects.activate', $subject))->assertRedirect();
    }

    public function test_inactive_assigned_teacher_blocks_activation_and_restore(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, $teacher, ['status' => Section::STATUS_INACTIVE]);
        $subject = $this->subject($school, $class, $teacher, ['status' => Subject::STATUS_INACTIVE]);

        $this->tenant($school, fn () => $teacher->update(['status' => Teacher::STATUS_INACTIVE]));
        $this->actingAs($admin)->patch(route('sections.activate', $section))->assertNotFound();
        $this->actingAs($admin)->patch(route('subjects.activate', $subject))->assertNotFound();

        $this->tenant($school, function () use ($section, $subject): void {
            $section->delete();
            $subject->delete();
        });
        $this->actingAs($admin)->patch(route('sections.restore', $section))->assertNotFound();
        $this->actingAs($admin)->patch(route('subjects.restore', $subject))->assertNotFound();
    }

    public function test_archived_teacher_identity_remains_visible_but_cannot_authorize_lifecycle_reentry(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser, 'T-1');
        $class = $this->schoolClass($school);
        $section = $this->section($school, $class, $teacher, [
            'name' => 'Historical Section',
            'status' => Section::STATUS_INACTIVE,
        ]);
        $subject = $this->subject($school, $class, $teacher, [
            'name' => 'Historical Subject',
            'code' => 'HISTORY',
            'status' => Subject::STATUS_INACTIVE,
        ]);

        $this->actingAs($admin)->patch(route('teacher-profiles.deactivate', $teacher))->assertRedirect();
        $this->actingAs($admin)->patch(route('teacher-profiles.archive', $teacher))->assertRedirect();

        $this->actingAs($admin)->get(route('sections.show', $section))
            ->assertOk()->assertSee($teacherUser->name)->assertDontSee('Not assigned');
        $this->actingAs($admin)->get(route('subjects.show', $subject))
            ->assertOk()->assertSee($teacherUser->name)->assertDontSee('Not assigned');
        $this->actingAs($admin)->get(route('sections.index'))->assertOk()->assertSee($teacherUser->name);
        $this->actingAs($admin)->get(route('subjects.index'))->assertOk()->assertSee($teacherUser->name);

        $this->actingAs($admin)->patch(route('sections.activate', $section))->assertNotFound();
        $this->actingAs($admin)->patch(route('subjects.activate', $subject))->assertNotFound();

        $this->tenant($school, function () use ($section, $subject): void {
            $section->delete();
            $subject->delete();
        });
        $this->actingAs($admin)->patch(route('sections.restore', $section))->assertNotFound();
        $this->actingAs($admin)->patch(route('subjects.restore', $subject))->assertNotFound();
    }

    public function test_cross_tenant_current_and_archived_route_binding_returns_not_found(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $classB = $this->schoolClass($schoolB);
        $sectionB = $this->section($schoolB, $classB, null, ['status' => Section::STATUS_INACTIVE]);
        $subjectB = $this->subject($schoolB, $classB, null, ['status' => Subject::STATUS_INACTIVE]);

        $this->actingAs($adminA)->get(route('sections.show', $sectionB))->assertNotFound();
        $this->actingAs($adminA)->put(route('subjects.update', $subjectB), $this->subjectPayload($classB))->assertNotFound();

        $this->tenant($schoolB, function () use ($sectionB, $subjectB): void {
            $sectionB->delete();
            $subjectB->delete();
        });
        $this->actingAs($adminA)->get(route('subjects.show', $subjectB))->assertNotFound();
        $this->actingAs($adminA)->patch(route('sections.restore', $sectionB))->assertNotFound();
    }

    public function test_exact_permissions_control_view_create_update_and_lifecycle_actions(): void
    {
        $school = $this->school('One');
        $class = $this->schoolClass($school);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $section = $this->section($school, $class);
        $subject = $this->subject($school, $class);
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $role->permissions()->detach(Permission::query()->where('code', 'academic.view')->firstOrFail());
        $this->actingAs($admin)->get(route('sections.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('subjects.index'))->assertForbidden();

        $this->seed();
        $role->permissions()->detach(Permission::query()->where('code', 'academic.create')->firstOrFail());
        $this->actingAs($admin)->post(route('sections.store'), $this->sectionPayload($class))->assertForbidden();

        $role->permissions()->detach(Permission::query()->where('code', 'academic.update')->firstOrFail());
        $this->actingAs($admin)->put(route('subjects.update', $subject), $this->subjectPayload($class))->assertForbidden();

        $role->permissions()->detach(Permission::query()->where('code', 'academic.delete')->firstOrFail());
        $this->actingAs($admin)->patch(route('sections.deactivate', $section))->assertForbidden();
    }

    public function test_direct_services_reject_wrong_context_platform_mutation_and_cross_tenant_relationships(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $classB = $this->schoolClass($schoolB);
        $sections = app(SectionService::class);
        $subjects = app(SubjectService::class);
        $context = app(TenantContext::class);

        foreach ([
            fn () => $sections->listFor($admin, []),
            fn () => $context->runAsPlatform(fn () => $subjects->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolB->id, fn () => $sections->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolA->id, fn () => $subjects->listFor($superAdmin, [])),
            fn () => $context->runAsPlatform(fn () => $sections->create($this->sectionPayload($classB), $superAdmin)),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(ModelNotFoundException::class);
        $context->runAsTenant($schoolA->id, fn () => $subjects->create($this->subjectPayload($classB), $admin));
    }

    public function test_logging_failures_roll_back_section_creation_and_subject_archive(): void
    {
        $school = $this->school('One');
        $class = $this->schoolClass($school);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Audit failed.'));
        $this->app->instance(SecurityLogService::class, $logger);

        try {
            $this->tenant($school, fn () => app(SectionService::class)->create($this->sectionPayload($class), $admin));
            $this->fail('Audit failure should escape.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit failed.', $exception->getMessage());
        }
        $this->assertDatabaseCount('sections', 0);

        $subject = $this->subject($school, $class, null, ['status' => Subject::STATUS_INACTIVE]);
        $archiveLogger = Mockery::mock(SecurityLogService::class);
        $archiveLogger->shouldReceive('activity')->once()->andThrow(new RuntimeException('Activity failed.'));
        $this->app->instance(SecurityLogService::class, $archiveLogger);

        try {
            $this->tenant($school, fn () => app(SubjectService::class)->archive($subject, $admin));
            $this->fail('Activity failure should escape.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Activity failed.', $exception->getMessage());
        }
        $this->assertNotSoftDeleted('subjects', ['id' => $subject->id]);
    }

    public function test_navigation_and_routes_expose_no_hard_delete_workflow(): void
    {
        $school = $this->school('One');
        $class = $this->schoolClass($school);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $section = $this->section($school, $class);
        $subject = $this->subject($school, $class);

        $this->actingAs($admin)->get(route('sections.show', $section))
            ->assertOk()->assertSee('Sections')->assertSee('Subjects')->assertSee('aria-current="page"', false);
        $this->actingAs($admin)->get(route('subjects.show', $subject))
            ->assertOk()->assertSee('aria-current="page"', false);
        $this->actingAs($admin)->delete('/sections/'.$section->id)->assertMethodNotAllowed();
        $this->actingAs($admin)->delete('/subjects/'.$subject->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'deleted_at' => null]);
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

    private function section(School $school, SchoolClass $class, ?Teacher $teacher = null, array $attributes = []): Section
    {
        return $this->tenant($school, fn () => Section::create([
            'class_id' => $class->id, 'teacher_id' => $teacher?->id,
            'name' => 'A', 'capacity' => 40, 'status' => Section::STATUS_ACTIVE,
            ...$attributes,
        ]));
    }

    private function subject(School $school, SchoolClass $class, ?Teacher $teacher = null, array $attributes = []): Subject
    {
        return $this->tenant($school, fn () => Subject::create([
            'class_id' => $class->id, 'teacher_id' => $teacher?->id,
            'name' => 'Mathematics', 'code' => 'MATH', 'subject_type' => Subject::TYPE_THEORY,
            'status' => Subject::STATUS_ACTIVE, ...$attributes,
        ]));
    }

    private function sectionPayload(SchoolClass $class, ?Teacher $teacher = null): array
    {
        return ['class_id' => $class->id, 'teacher_id' => $teacher?->id, 'name' => 'B', 'capacity' => 40];
    }

    private function subjectPayload(SchoolClass $class, ?Teacher $teacher = null): array
    {
        return [
            'class_id' => $class->id, 'teacher_id' => $teacher?->id,
            'name' => 'Science', 'code' => 'SCI', 'subject_type' => Subject::TYPE_THEORY,
        ];
    }

    private function superAdmin(): User
    {
        return User::query()->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))->firstOrFail();
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
