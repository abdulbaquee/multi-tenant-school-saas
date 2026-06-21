<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\AcademicTermService;
use App\Services\AcademicYearService;
use App\Services\SecurityLogService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AcademicYearTermManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_from_every_academic_year_and_term_workflow(): void
    {
        $school = $this->school('One');
        $year = $this->year($school);
        $term = $this->term($school, $year);

        $requests = [
            fn () => $this->get(route('academic-years.index')),
            fn () => $this->get(route('academic-years.create')),
            fn () => $this->post(route('academic-years.store'), $this->yearPayload()),
            fn () => $this->get(route('academic-years.show', $year)),
            fn () => $this->get(route('academic-years.edit', $year)),
            fn () => $this->put(route('academic-years.update', $year), $this->yearPayload()),
            fn () => $this->patch(route('academic-years.activate', $year)),
            fn () => $this->patch(route('academic-years.deactivate', $year)),
            fn () => $this->patch(route('academic-years.reactivate', $year)),
            fn () => $this->get(route('academic-terms.index')),
            fn () => $this->get(route('academic-terms.create')),
            fn () => $this->post(route('academic-terms.store'), $this->termPayload($year)),
            fn () => $this->get(route('academic-terms.show', $term)),
            fn () => $this->get(route('academic-terms.edit', $term)),
            fn () => $this->put(route('academic-terms.update', $term), $this->termPayload($year)),
            fn () => $this->patch(route('academic-terms.deactivate', $term)),
            fn () => $this->patch(route('academic-terms.reactivate', $term)),
        ];

        foreach ($requests as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_has_platform_read_only_access_across_schools(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $yearA = $this->year($schoolA, ['name' => 'School A 2026']);
        $yearB = $this->year($schoolB, ['name' => 'School B 2026']);
        $termA = $this->term($schoolA, $yearA, ['name' => 'A Term']);
        $termB = $this->term($schoolB, $yearB, ['name' => 'B Term']);
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee($yearA->name)
            ->assertSee($yearB->name)
            ->assertSee($schoolA->name)
            ->assertSee($schoolB->name)
            ->assertDontSee('New Academic Year');

        $this->actingAs($superAdmin)->get(route('academic-years.show', $yearA))->assertOk();
        $this->actingAs($superAdmin)
            ->get(route('academic-terms.index'))
            ->assertOk()
            ->assertSee($termA->name)
            ->assertSee($termB->name)
            ->assertDontSee('New Academic Term');
        $this->actingAs($superAdmin)->get(route('academic-terms.show', $termB))->assertOk();

        $this->actingAs($superAdmin)->get(route('academic-years.create'))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('academic-years.store'), $this->yearPayload())->assertForbidden();
        $this->actingAs($superAdmin)->get(route('academic-years.edit', $yearA))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('academic-years.activate', $yearA))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('academic-terms.create'))->assertForbidden();
        $this->actingAs($superAdmin)->patch(route('academic-terms.deactivate', $termA))->assertForbidden();
    }

    public function test_teacher_accountant_and_malformed_school_users_are_denied_year_and_term_administration(): void
    {
        $school = $this->school('One');
        $year = $this->year($school);
        $term = $this->term($school, $year);

        foreach ([Role::TEACHER, Role::ACCOUNTANT] as $index => $roleCode) {
            $actor = $this->schoolUser($roleCode, $school, $roleCode.$index.'@example.com');

            $this->actingAs($actor)->get(route('academic-years.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('academic-years.show', $year))->assertForbidden();
            $this->actingAs($actor)->get(route('academic-terms.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('academic-terms.show', $term))->assertForbidden();
            $this->actingAs($actor)->get(route('dashboard'))->assertDontSee(route('academic-years.index'), false);
        }

        $malformed = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'malformed@example.com');
        $malformed->forceFill(['school_id' => null])->save();

        $this->actingAs($malformed)->get(route('academic-years.index'))->assertRedirect(route('login'));
    }

    public function test_school_admin_can_create_and_update_an_academic_year_with_tenant_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $response = $this->actingAs($admin)->post(route('academic-years.store'), [
            ...$this->yearPayload(),
            'name' => '  2026-2027  ',
            'school_id' => 999,
            'status' => AcademicYear::STATUS_INACTIVE,
            'is_current' => true,
        ]);

        $response->assertSessionHasErrors(['school_id', 'status', 'is_current']);
        $this->assertDatabaseMissing('academic_years', ['name' => '2026-2027']);

        $response = $this->actingAs($admin)->post(route('academic-years.store'), [
            ...$this->yearPayload(),
            'name' => '  2026-2027  ',
        ]);

        $yearId = (int) DB::table('academic_years')->where('name', '2026-2027')->value('id');
        $response->assertRedirect(route('academic-years.show', $yearId));
        $this->assertDatabaseHas('academic_years', [
            'id' => $yearId,
            'school_id' => $school->id,
            'name' => '2026-2027',
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_current' => false,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'module' => 'academic_structure',
            'action' => 'created',
            'subject_type' => AcademicYear::class,
            'subject_id' => $yearId,
        ]);

        $this->actingAs($admin)->put(route('academic-years.update', $yearId), [
            'name' => 'Academic Year 2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-30',
        ])->assertRedirect(route('academic-years.show', $yearId));

        $this->assertSame(
            'Academic Year 2026-27',
            DB::table('academic_years')->where('id', $yearId)->value('name'),
        );
        $this->assertStringStartsWith(
            '2027-03-30',
            (string) DB::table('academic_years')->where('id', $yearId)->value('end_date'),
        );
        $audit = DB::table('audit_logs')
            ->where('auditable_type', AcademicYear::class)
            ->where('auditable_id', $yearId)
            ->where('event', 'updated')
            ->latest('id')
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame(['name', 'end_date'], array_keys(json_decode($audit->new_values, true, flags: JSON_THROW_ON_ERROR)));
    }

    public function test_year_date_and_tenant_unique_rules_reject_overlap_without_partial_writes(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->year($school, [
            'name' => 'Existing Year',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);

        $this->actingAs($admin)->post(route('academic-years.store'), [
            'name' => 'Existing Year',
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
        ])->assertSessionHasErrors('name');

        $this->actingAs($admin)->post(route('academic-years.store'), [
            'name' => 'Touching Boundary',
            'start_date' => '2027-03-31',
            'end_date' => '2028-03-30',
        ])->assertSessionHasErrors('start_date');

        $this->actingAs($admin)->post(route('academic-years.store'), [
            'name' => 'Invalid Dates',
            'start_date' => '2028-04-01',
            'end_date' => '2028-04-01',
        ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_year_update_cannot_exclude_a_retained_term(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);
        $this->term($school, $year, [
            'start_date' => '2026-04-01',
            'end_date' => '2026-09-30',
        ]);

        $this->actingAs($admin)->put(route('academic-years.update', $year), [
            'name' => $year->name,
            'start_date' => '2026-05-01',
            'end_date' => '2027-03-31',
        ])->assertSessionHasErrors('start_date');

        $this->assertStringStartsWith(
            '2026-04-01',
            (string) DB::table('academic_years')->where('id', $year->id)->value('start_date'),
        );
    }

    public function test_school_admin_can_create_and_update_a_valid_academic_term(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);

        $response = $this->actingAs($admin)->post(route('academic-terms.store'), [
            ...$this->termPayload($year),
            'name' => '  Term One  ',
        ]);

        $termId = (int) DB::table('academic_terms')->where('name', 'Term One')->value('id');
        $response->assertRedirect(route('academic-terms.show', $termId));
        $this->assertDatabaseHas('academic_terms', [
            'id' => $termId,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_order' => 1,
            'status' => AcademicTerm::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->put(route('academic-terms.update', $termId), [
            ...$this->termPayload($year),
            'name' => 'Foundation Term',
            'term_order' => 2,
        ])->assertRedirect(route('academic-terms.show', $termId));

        $this->assertDatabaseHas('academic_terms', [
            'id' => $termId,
            'name' => 'Foundation Term',
            'term_order' => 2,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'module' => 'academic_structure',
            'action' => 'updated',
            'subject_type' => AcademicTerm::class,
            'subject_id' => $termId,
        ]);
    }

    public function test_term_parent_containment_overlap_unique_order_and_forged_fields_are_rejected(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $yearA = $this->year($schoolA);
        $yearB = $this->year($schoolB, ['name' => 'Other School Year']);
        $this->term($schoolA, $yearA, [
            'name' => 'Term One',
            'term_order' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2026-09-30',
        ]);

        $this->actingAs($admin)->post(route('academic-terms.store'), [
            ...$this->termPayload($yearB),
            'school_id' => $schoolB->id,
            'status' => AcademicTerm::STATUS_INACTIVE,
        ])->assertSessionHasErrors(['academic_year_id', 'school_id', 'status']);

        $this->actingAs($admin)->post(route('academic-terms.store'), [
            'academic_year_id' => $yearA->id,
            'name' => 'Unique Order Check',
            'term_order' => 1,
            'start_date' => '2026-10-01',
            'end_date' => '2027-01-31',
        ])->assertSessionHasErrors('term_order');

        $this->actingAs($admin)->post(route('academic-terms.store'), [
            'academic_year_id' => $yearA->id,
            'name' => 'Touching Boundary',
            'term_order' => 2,
            'start_date' => '2026-09-30',
            'end_date' => '2027-01-31',
        ])->assertSessionHasErrors('start_date');

        $this->actingAs($admin)->post(route('academic-terms.store'), [
            'academic_year_id' => $yearA->id,
            'name' => 'Outside Term',
            'term_order' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ])->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('academic_terms', 1);
    }

    public function test_activating_a_year_serially_replaces_the_previous_current_year(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $oldYear = $this->year($school, [
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
        ]);
        $newYear = $this->year($school, [
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);

        $this->actingAs($admin)
            ->patch(route('academic-years.activate', $newYear))
            ->assertRedirect(route('academic-years.show', $newYear));

        $this->assertDatabaseHas('academic_years', ['id' => $oldYear->id, 'is_current' => false]);
        $this->assertDatabaseHas('academic_years', ['id' => $newYear->id, 'is_current' => true]);
        $this->assertSame(1, DB::table('academic_years')->where('school_id', $school->id)->where('is_current', true)->count());
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'action' => 'activated',
            'subject_type' => AcademicYear::class,
            'subject_id' => $newYear->id,
        ]);

        $this->actingAs($admin)->patch(route('academic-years.activate', $newYear))->assertForbidden();
    }

    public function test_year_deactivation_requires_non_current_status_and_inactive_terms_then_can_reactivate(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $currentYear = $this->year($school, ['is_current' => true]);

        $this->actingAs($admin)->patch(route('academic-years.deactivate', $currentYear))->assertForbidden();

        $currentYear = $this->year($school, [
            'name' => 'Future Year',
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
        ]);
        $term = $this->term($school, $currentYear, [
            'start_date' => '2027-04-01',
            'end_date' => '2027-09-30',
        ]);

        $this->actingAs($admin)
            ->patch(route('academic-years.deactivate', $currentYear))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('academic_years', ['id' => $currentYear->id, 'status' => AcademicYear::STATUS_ACTIVE]);

        $this->actingAs($admin)->patch(route('academic-terms.deactivate', $term))->assertRedirect();
        $this->actingAs($admin)->patch(route('academic-years.deactivate', $currentYear))->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $currentYear->id, 'status' => AcademicYear::STATUS_INACTIVE]);

        $this->actingAs($admin)->patch(route('academic-years.reactivate', $currentYear))->assertRedirect();
        $this->assertDatabaseHas('academic_years', [
            'id' => $currentYear->id,
            'status' => AcademicYear::STATUS_ACTIVE,
            'is_current' => false,
        ]);
    }

    public function test_term_can_deactivate_and_reactivate_only_under_an_active_parent_year(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);
        $term = $this->term($school, $year);

        $this->actingAs($admin)->patch(route('academic-terms.deactivate', $term))->assertRedirect();
        $this->assertDatabaseHas('academic_terms', ['id' => $term->id, 'status' => AcademicTerm::STATUS_INACTIVE]);

        DB::table('academic_years')->where('id', $year->id)->update(['status' => AcademicYear::STATUS_INACTIVE]);
        $this->actingAs($admin)
            ->patch(route('academic-terms.reactivate', $term))
            ->assertSessionHasErrors('academic_year_id');
        $this->assertDatabaseHas('academic_terms', ['id' => $term->id, 'status' => AcademicTerm::STATUS_INACTIVE]);

        DB::table('academic_years')->where('id', $year->id)->update(['status' => AcademicYear::STATUS_ACTIVE]);
        $this->actingAs($admin)->patch(route('academic-terms.reactivate', $term))->assertRedirect();
        $this->assertDatabaseHas('academic_terms', ['id' => $term->id, 'status' => AcademicTerm::STATUS_ACTIVE]);
    }

    public function test_cross_tenant_lists_route_binding_parent_ids_and_mutations_do_not_leak(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $yearA = $this->year($schoolA, ['name' => 'Visible Year']);
        $yearB = $this->year($schoolB, ['name' => 'Hidden Year']);
        $termA = $this->term($schoolA, $yearA, ['name' => 'Visible Term']);
        $termB = $this->term($schoolB, $yearB, ['name' => 'Hidden Term']);

        $this->actingAs($adminA)
            ->get(route('academic-years.index'))
            ->assertOk()
            ->assertSee($yearA->name)
            ->assertDontSee($yearB->name);
        $this->actingAs($adminA)
            ->get(route('academic-terms.index'))
            ->assertOk()
            ->assertSee($termA->name)
            ->assertDontSee($termB->name);

        $this->actingAs($adminA)->get(route('academic-years.show', $yearB))->assertNotFound();
        $this->actingAs($adminA)->put(route('academic-years.update', $yearB), $this->yearPayload())->assertNotFound();
        $this->actingAs($adminA)->get(route('academic-terms.show', $termB))->assertNotFound();
        $this->actingAs($adminA)->patch(route('academic-terms.deactivate', $termB))->assertNotFound();
        $this->actingAs($adminA)
            ->get(route('academic-terms.index', ['academic_year_id' => $yearB->id]))
            ->assertSessionHasErrors('academic_year_id');
    }

    public function test_exact_permissions_control_routes_actions_and_navigation_immediately(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $this->actingAs($admin)->get(route('dashboard'))->assertSee(route('academic-years.index'), false);
        $this->actingAs($admin)->get(route('academic-years.index'))->assertOk()->assertSee('New Academic Year');

        $viewPermission = Permission::query()->where('code', 'academic.view')->firstOrFail();
        $role->permissions()->detach($viewPermission);

        $this->actingAs($admin)->get(route('academic-years.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('academic-years.index'), false);

        $role->permissions()->attach($viewPermission);
        $createPermission = Permission::query()->where('code', 'academic.create')->firstOrFail();
        $role->permissions()->detach($createPermission);

        $this->actingAs($admin)->get(route('academic-years.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('academic-years.store'), $this->yearPayload())->assertForbidden();

        $role->permissions()->attach($createPermission);
        $updatePermission = Permission::query()->where('code', 'academic.update')->firstOrFail();
        $role->permissions()->detach($updatePermission);

        $this->actingAs($admin)->get(route('academic-years.show', $year))->assertOk()->assertDontSee('Set as Current');
        $this->actingAs($admin)->patch(route('academic-years.activate', $year))->assertForbidden();

        $role->permissions()->attach($updatePermission);
        $deletePermission = Permission::query()->where('code', 'academic.delete')->firstOrFail();
        $role->permissions()->detach($deletePermission);

        $this->actingAs($admin)->patch(route('academic-years.deactivate', $year))->assertForbidden();
    }

    public function test_direct_services_reject_wrong_contexts_and_platform_mutation(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $context = app(TenantContext::class);
        $yearService = app(AcademicYearService::class);
        $termService = app(AcademicTermService::class);

        foreach ([
            fn () => $yearService->listFor($admin, []),
            fn () => $context->runAsPlatform(fn () => $yearService->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolB->id, fn () => $termService->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolA->id, fn () => $yearService->listFor($superAdmin, [])),
            fn () => $context->runAsPlatform(fn () => $yearService->create($this->yearPayload(), $superAdmin)),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_logging_failure_rolls_back_year_creation_and_current_activation(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $oldYear = $this->year($school, [
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
        ]);
        $newYear = $this->year($school, [
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->andThrow(new RuntimeException('Forced logging failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(AcademicYearService::class);
        $context = app(TenantContext::class);

        try {
            $context->runAsTenant($school->id, fn () => $service->create([
                'name' => '2027-2028',
                'start_date' => '2027-04-01',
                'end_date' => '2028-03-31',
            ], $admin));
            $this->fail('The forced logging failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced logging failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('academic_years', ['name' => '2027-2028']);

        try {
            $context->runAsTenant($school->id, fn () => $service->makeCurrent($newYear, $admin));
            $this->fail('The forced logging failure should escape the transaction.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('academic_years', ['id' => $oldYear->id, 'is_current' => true]);
        $this->assertDatabaseHas('academic_years', ['id' => $newYear->id, 'is_current' => false]);
    }

    public function test_audit_failure_rolls_back_academic_term_creation(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced audit failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(AcademicTermService::class);

        try {
            app(TenantContext::class)->runAsTenant(
                $school->id,
                fn () => $service->create($this->termPayload($year), $admin),
            );
            $this->fail('The forced audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('academic_terms', 0);
    }

    public function test_academic_navigation_has_one_active_entry_and_no_delete_routes_exist(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $year = $this->year($school);
        $term = $this->term($school, $year);

        $this->actingAs($admin)
            ->get(route('academic-terms.show', $term))
            ->assertOk()
            ->assertSee('Academic Structure')
            ->assertSee('aria-current="page"', false);

        $this->actingAs($admin)->delete('/academic-years/'.$year->id)->assertMethodNotAllowed();
        $this->actingAs($admin)->delete('/academic-terms/'.$term->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
        $this->assertDatabaseHas('academic_terms', ['id' => $term->id]);
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function year(School $school, array $attributes = []): AcademicYear
    {
        return app(TenantContext::class)->runAsTenant($school->id, fn () => AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => false,
            'status' => AcademicYear::STATUS_ACTIVE,
            ...$attributes,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function term(School $school, AcademicYear $year, array $attributes = []): AcademicTerm
    {
        return app(TenantContext::class)->runAsTenant($school->id, fn () => AcademicTerm::create([
            'academic_year_id' => $year->id,
            'name' => 'Term One',
            'term_order' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2026-09-30',
            'status' => AcademicTerm::STATUS_ACTIVE,
            ...$attributes,
        ]));
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
    private function yearPayload(): array
    {
        return [
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function termPayload(AcademicYear $year): array
    {
        return [
            'academic_year_id' => $year->id,
            'name' => 'Term One',
            'term_order' => 1,
            'start_date' => '2026-04-01',
            'end_date' => '2026-09-30',
        ];
    }
}
