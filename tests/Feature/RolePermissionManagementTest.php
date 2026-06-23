<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Services\SecurityLogService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_from_every_role_management_route(): void
    {
        $role = $this->role(Role::TEACHER);

        $this->get(route('roles.index'))->assertRedirect(route('login'));
        $this->get(route('roles.show', $role))->assertRedirect(route('login'));
        $this->get(route('roles.edit', $role))->assertRedirect(route('login'));
        $this->put(route('roles.permissions.update', $role), [
            'mapping_fingerprint' => str_repeat('a', 64),
            'permission_ids' => [],
        ])->assertRedirect(route('login'));
    }

    public function test_mapping_updates_reject_missing_csrf_tokens_without_writing(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $originalCodes = $this->codes($teacher);
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $this->actingAs($superAdmin)
                ->put(route('roles.permissions.update', $teacher), $this->mappingPayload($teacher))
                ->assertStatus(419);
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }

        $this->assertSame($originalCodes, $this->codes($teacher));
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_super_admin_can_view_all_roles_and_edit_only_school_role_mappings(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->get(route('roles.index'));

        $response->assertOk()
            ->assertSee('Roles & Permissions')
            ->assertSee('Super Admin')
            ->assertSee('School Admin')
            ->assertSee('Teacher')
            ->assertSee('Accountant')
            ->assertSee('table-responsive', false);

        $this->actingAs($superAdmin)
            ->get(route('roles.show', $this->role(Role::SUPER_ADMIN)))
            ->assertOk()
            ->assertSee('fixed and cannot be edited');

        $this->actingAs($superAdmin)
            ->get(route('roles.edit', $this->role(Role::TEACHER)))
            ->assertOk()
            ->assertSee('Essential')
            ->assertSee('confirmMappingModal')
            ->assertSee('col-md-6 col-xl-4', false);

        $this->actingAs($superAdmin)
            ->get(route('roles.edit', $this->role(Role::SUPER_ADMIN)))
            ->assertForbidden();
        $this->actingAs($superAdmin)
            ->put(route('roles.permissions.update', $this->role(Role::SUPER_ADMIN)), $this->mappingPayload($this->role(Role::SUPER_ADMIN)))
            ->assertForbidden();
    }

    public function test_school_admin_has_read_only_visibility_of_only_school_roles(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-one@example.com');

        $response = $this->actingAs($schoolAdmin)->get(route('roles.index'));

        $response->assertOk()
            ->assertDontSee('Super Admin')
            ->assertSee('School Admin')
            ->assertSee('Teacher')
            ->assertSee('Accountant')
            ->assertDontSee('Edit Mapping');

        $this->actingAs($schoolAdmin)
            ->get(route('roles.show', $this->role(Role::TEACHER)))
            ->assertOk()
            ->assertSee('read-only');
        $this->actingAs($schoolAdmin)
            ->get(route('roles.show', $this->role(Role::SUPER_ADMIN)))
            ->assertForbidden();
        $this->actingAs($schoolAdmin)
            ->get(route('roles.edit', $this->role(Role::TEACHER)))
            ->assertForbidden();
        $this->actingAs($schoolAdmin)
            ->put(route('roles.permissions.update', $this->role(Role::TEACHER)), $this->mappingPayload($this->role(Role::TEACHER)))
            ->assertForbidden();
    }

    public function test_teacher_accountant_and_malformed_users_are_denied_role_management(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $malformed = User::factory()->create([
            'school_id' => null,
            'role_id' => $this->role(Role::SCHOOL_ADMIN)->id,
            'email' => 'malformed@example.com',
        ]);

        $this->actingAs($teacher)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($malformed)->get(route('roles.index'))->assertRedirect(route('login'));

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Roles & Permissions');
    }

    public function test_super_admin_can_replace_a_school_role_mapping_while_retaining_essentials(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $permission = $this->permission('students.view');
        $fingerprint = $this->fingerprint($teacher);

        $this->actingAs($superAdmin)
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => $this->permissionIds([
                    ...config('rbac.essential_permissions.teacher'),
                    $permission->code,
                ]),
            ])
            ->assertRedirect(route('roles.show', $teacher))
            ->assertSessionHas('status', 'Role permission mapping updated successfully.');

        $actualCodes = $this->codes($teacher);
        $expectedCodes = ['dashboard.view', 'profile.update', 'profile.view', 'students.view'];
        sort($expectedCodes);

        $this->assertSame($expectedCodes, $actualCodes);
    }

    public function test_mapping_update_writes_sorted_sanitized_platform_activity_and_audit_records(): void
    {
        $superAdmin = $this->superAdmin();
        $accountant = $this->role(Role::ACCOUNTANT);
        $oldCodes = $this->codes($accountant);
        $desiredCode = 'fees.view';

        $this->actingAs($superAdmin)
            ->put(route('roles.permissions.update', $accountant), [
                'mapping_fingerprint' => $this->fingerprint($accountant),
                'permission_ids' => $this->permissionIds([
                    ...config('rbac.essential_permissions.accountant'),
                    $desiredCode,
                ]),
            ])
            ->assertRedirect(route('roles.show', $accountant));

        $context = app(TenantContext::class);
        $context->setPlatform();

        $activity = ActivityLog::query()->latest('id')->firstOrFail();
        $audit = AuditLog::query()->latest('id')->firstOrFail();
        $newCodes = ['dashboard.view', 'fees.view', 'profile.update', 'profile.view'];
        sort($newCodes);

        $this->assertNull($activity->school_id);
        $this->assertSame($superAdmin->id, $activity->user_id);
        $this->assertSame('role_permissions', $activity->module);
        $this->assertSame('mapping_updated', $activity->action);
        $this->assertSame(Role::class, $activity->subject_type);
        $this->assertSame($accountant->id, $activity->subject_id);

        $this->assertNull($audit->school_id);
        $this->assertSame(Role::class, $audit->auditable_type);
        $this->assertSame($accountant->id, $audit->auditable_id);
        $this->assertSame('mapping_updated', $audit->event);
        $this->assertSame(['permissions' => $oldCodes], $audit->old_values);
        $this->assertSame(['permissions' => $newCodes], $audit->new_values);
        $context->clear();
    }

    public function test_forged_duplicate_and_out_of_bound_permissions_fail_without_partial_writes(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $originalCodes = $this->codes($teacher);
        $fingerprint = $this->fingerprint($teacher);
        $allowedId = $this->permission('students.view')->id;

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => [999999],
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('permission_ids.0');
        $this->assertSame($originalCodes, $this->codes($teacher));

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => [$allowedId, $allowedId],
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('permission_ids.1');
        $this->assertSame($originalCodes, $this->codes($teacher));

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => $this->permissionIds([
                    ...config('rbac.essential_permissions.teacher'),
                    'fees.collect',
                ]),
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('permission_ids');
        $this->assertSame($originalCodes, $this->codes($teacher));
    }

    public function test_accountant_fee_setup_report_and_export_permissions_are_out_of_bounds(): void
    {
        $superAdmin = $this->superAdmin();
        $accountant = $this->role(Role::ACCOUNTANT);
        $originalCodes = $this->codes($accountant);

        foreach (['fees.create', 'fees.update', 'fees.delete', 'fees.report', 'fees.export'] as $code) {
            $this->actingAs($superAdmin)
                ->from(route('roles.edit', $accountant))
                ->put(route('roles.permissions.update', $accountant), [
                    'mapping_fingerprint' => $this->fingerprint($accountant),
                    'permission_ids' => $this->permissionIds([
                        ...config('rbac.essential_permissions.accountant'),
                        $code,
                    ]),
                ])
                ->assertRedirect(route('roles.edit', $accountant))
                ->assertSessionHasErrors('permission_ids');

            $this->assertSame($originalCodes, $this->codes($accountant));
        }
    }

    public function test_teacher_exam_setup_publish_report_and_export_permissions_are_out_of_bounds(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $originalCodes = $this->codes($teacher);

        foreach (['exams.delete', 'exams.publish', 'exams.report', 'exams.export'] as $code) {
            $this->actingAs($superAdmin)
                ->from(route('roles.edit', $teacher))
                ->put(route('roles.permissions.update', $teacher), [
                    'mapping_fingerprint' => $this->fingerprint($teacher),
                    'permission_ids' => $this->permissionIds([
                        ...config('rbac.essential_permissions.teacher'),
                        $code,
                    ]),
                ])
                ->assertRedirect(route('roles.edit', $teacher))
                ->assertSessionHasErrors('permission_ids');

            $this->assertSame($originalCodes, $this->codes($teacher));
        }

        $this->assertEqualsCanonicalizing(
            ['exams.view', 'exams.create', 'exams.update'],
            array_values(array_intersect(
                config('rbac.default_mappings.teacher'),
                ['exams.view', 'exams.create', 'exams.update', 'exams.delete', 'exams.publish', 'exams.report', 'exams.export'],
            )),
        );
    }

    public function test_essential_removal_and_empty_payload_fail_without_partial_writes(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $originalCodes = $this->codes($teacher);
        $fingerprint = $this->fingerprint($teacher);

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => [$this->permission('students.view')->id],
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('permission_ids');
        $this->assertSame($originalCodes, $this->codes($teacher));

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $fingerprint,
                'permission_ids' => [],
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('permission_ids');
        $this->assertSame($originalCodes, $this->codes($teacher));
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_stale_mapping_submission_is_rejected_without_overwriting_latest_mapping(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $staleFingerprint = $this->fingerprint($teacher);

        $this->removePermission($teacher, 'students.view');
        $latestCodes = $this->codes($teacher);

        $this->actingAs($superAdmin)
            ->from(route('roles.edit', $teacher))
            ->put(route('roles.permissions.update', $teacher), [
                'mapping_fingerprint' => $staleFingerprint,
                'permission_ids' => [$this->permission('students.view')->id],
            ])
            ->assertRedirect(route('roles.edit', $teacher))
            ->assertSessionHasErrors('mapping_fingerprint');

        $this->assertSame($latestCodes, $this->codes($teacher));
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_logging_failure_rolls_back_the_mapping_replacement(): void
    {
        $superAdmin = $this->superAdmin();
        $teacher = $this->role(Role::TEACHER);
        $originalCodes = $this->codes($teacher);
        $context = app(TenantContext::class);
        $context->setPlatform();

        $securityLogs = Mockery::mock(SecurityLogService::class);
        $securityLogs->shouldReceive('platformActivity')
            ->once()
            ->andThrow(new RuntimeException('Forced logging failure.'));

        $service = new RolePermissionService($context, $securityLogs);

        try {
            $service->updateMapping(
                $teacher,
                $this->permissionIds([
                    ...config('rbac.essential_permissions.teacher'),
                    'students.view',
                ]),
                $service->fingerprintForCodes($originalCodes),
                $superAdmin,
            );
            $this->fail('The forced logging failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced logging failure.', $exception->getMessage());
        }

        $this->assertSame($originalCodes, $this->codes($teacher));
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $context->clear();
    }

    public function test_direct_service_calls_require_actor_aligned_context_and_authorization(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $inactiveSchool = $this->school('Inactive');
        $inactiveSchool->update(['status' => School::STATUS_INACTIVE]);
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $inactiveSchoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $inactiveSchool, 'inactive-admin@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $schoolOne, 'teacher@example.com');
        $superAdmin = $this->superAdmin();
        $service = app(RolePermissionService::class);
        $context = app(TenantContext::class);

        foreach ([
            fn () => $service->listFor($schoolAdmin),
            function () use ($context, $service, $schoolAdmin, $schoolTwo): void {
                $context->setTenant($schoolTwo->id);
                $service->listFor($schoolAdmin);
            },
            function () use ($context, $service, $schoolAdmin): void {
                $context->setPlatform();
                $service->listFor($schoolAdmin);
            },
            function () use ($context, $service, $superAdmin, $schoolOne): void {
                $context->setTenant($schoolOne->id);
                $service->listFor($superAdmin);
            },
            function () use ($context, $service, $inactiveSchoolAdmin, $inactiveSchool): void {
                $context->setTenant($inactiveSchool->id);
                $service->listFor($inactiveSchoolAdmin);
            },
            function () use ($context, $service, $teacher, $schoolOne): void {
                $context->setTenant($schoolOne->id);
                $service->detailsFor($this->role(Role::TEACHER), $teacher);
            },
            function () use ($context, $service, $schoolAdmin, $schoolOne): void {
                $context->setTenant($schoolOne->id);
                $role = $this->role(Role::TEACHER);
                $service->updateMapping(
                    $role,
                    $this->permissionIds(config('rbac.default_mappings.teacher')),
                    $service->fingerprintForCodes($this->codes($role)),
                    $schoolAdmin,
                );
            },
        ] as $call) {
            try {
                $context->clear();
                $call();
                $this->fail('An unauthorized direct service call succeeded.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        $context->clear();
    }

    public function test_mapping_revocation_changes_menu_and_action_access_on_the_next_request(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $role = $this->role(Role::SCHOOL_ADMIN);
        $desiredIds = $role->permissions()
            ->where('code', '!=', 'users.create')
            ->pluck('permissions.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $this->actingAs($superAdmin)
            ->put(route('roles.permissions.update', $role), [
                'mapping_fingerprint' => $this->fingerprint($role),
                'permission_ids' => $desiredIds,
            ])
            ->assertRedirect(route('roles.show', $role));

        $this->actingAs($schoolAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('New User');
        $this->actingAs($schoolAdmin)
            ->get(route('users.create'))
            ->assertForbidden();

        $restoredIds = $role->permissions()
            ->pluck('permissions.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->push($this->permission('users.create')->id)
            ->unique()
            ->values()
            ->all();

        $this->actingAs($superAdmin)
            ->put(route('roles.permissions.update', $role), [
                'mapping_fingerprint' => $this->fingerprint($role),
                'permission_ids' => $restoredIds,
            ])
            ->assertRedirect(route('roles.show', $role));

        $this->actingAs($schoolAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('New User');
    }

    public function test_sidebar_visibility_and_active_state_follow_role_authorization(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->actingAs($superAdmin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Roles & Permissions')
            ->assertSeeInOrder(['sidebar-link', 'active'], false)
            ->assertSee('aria-current="page"', false);

        $this->actingAs($schoolAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Roles & Permissions');

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Roles & Permissions');
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ROLE-'.$suffix,
            'email' => strtolower('role-'.$suffix).'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => $this->role($roleCode)->id,
            'email' => $email,
        ]);
    }

    private function role(string $code): Role
    {
        return Role::query()->where('code', $code)->firstOrFail();
    }

    private function permission(string $code): Permission
    {
        return Permission::query()->where('code', $code)->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function codes(Role $role): array
    {
        $codes = $role->permissions()->pluck('code')->all();
        sort($codes);

        return array_values($codes);
    }

    private function fingerprint(Role $role): string
    {
        return app(RolePermissionService::class)->fingerprintForCodes($this->codes($role));
    }

    /**
     * @return array{mapping_fingerprint: string, permission_ids: list<int>}
     */
    private function mappingPayload(Role $role): array
    {
        return [
            'mapping_fingerprint' => $this->fingerprint($role),
            'permission_ids' => $role->permissions()->pluck('permissions.id')->map(fn (mixed $id): int => (int) $id)->all(),
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<int>
     */
    private function permissionIds(array $codes): array
    {
        return Permission::query()
            ->whereIn('code', $codes)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function addPermission(Role $role, string $permissionCode): void
    {
        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $role->id,
                'permission_id' => $this->permission($permissionCode)->id,
            ],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    private function removePermission(Role $role, string $permissionCode): void
    {
        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $this->permission($permissionCode)->id)
            ->delete();
    }
}
