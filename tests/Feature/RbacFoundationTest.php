<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class RbacFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_canonical_catalog_and_default_mappings_match_rbac_configuration(): void
    {
        $this->assertDatabaseCount('roles', count(config('rbac.roles')));
        $this->assertDatabaseCount('permissions', count(config('rbac.permissions')));

        foreach (array_keys(config('rbac.roles')) as $roleCode) {
            $role = Role::query()->where('code', $roleCode)->firstOrFail();
            $actualCodes = $role->permissions()->orderBy('code')->pluck('code')->all();
            $expectedCodes = config("rbac.default_mappings.{$roleCode}");
            sort($expectedCodes);

            $this->assertSame($expectedCodes, $actualCodes, "Unexpected {$roleCode} mapping.");
            $this->assertEqualsCanonicalizing(
                [],
                array_diff($actualCodes, config("rbac.maximum_mappings.{$roleCode}")),
            );
            $this->assertEqualsCanonicalizing(
                [],
                array_diff(config("rbac.essential_permissions.{$roleCode}"), $actualCodes),
            );
        }

        $this->assertNotContains(
            'academic.create',
            config('rbac.maximum_mappings.super_admin'),
        );
    }

    public function test_reseeding_repairs_mapping_boundaries_without_restoring_allowed_revocations_or_passwords(): void
    {
        $schoolAdmin = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();
        $superAdmin = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
        $customPassword = Hash::make('CustomPassword123');
        $superAdmin->forceFill(['password' => $customPassword])->save();

        $this->removePermission($schoolAdmin, 'users.create');
        $this->removePermission($schoolAdmin, 'users.view');
        $this->addPermission($schoolAdmin, 'schools.view');

        $this->seed();

        $schoolAdminCodes = $schoolAdmin->permissions()->pluck('code')->all();
        $this->assertNotContains('users.create', $schoolAdminCodes);
        $this->assertContains('users.view', $schoolAdminCodes);
        $this->assertNotContains('schools.view', $schoolAdminCodes);
        $this->assertSame($customPassword, $superAdmin->fresh()->password);

        $superAdminRole = Role::query()->where('code', Role::SUPER_ADMIN)->firstOrFail();
        $this->assertEqualsCanonicalizing(
            config('rbac.maximum_mappings.super_admin'),
            $superAdminRole->permissions()->pluck('code')->all(),
        );
    }

    public function test_role_and_permission_catalog_models_are_immutable(): void
    {
        $role = Role::query()->where('code', Role::TEACHER)->firstOrFail();
        $permission = Permission::query()->where('code', 'students.view')->firstOrFail();

        try {
            $role->update(['name' => 'Changed Role']);
            $this->fail('A system role was updated through Eloquent.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        try {
            $permission->delete();
            $this->fail('A system permission was deleted through Eloquent.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_fresh_bootstrap_requires_an_explicit_complex_super_admin_password(): void
    {
        DB::table('users')->where('email', 'superadmin@example.com')->delete();
        config()->set('rbac.bootstrap_super_admin.password', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SUPER_ADMIN_PASSWORD must contain');

        $this->seed();
    }

    public function test_permission_changes_apply_without_cache_and_control_current_routes(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $role = $schoolAdmin->role;

        $this->removePermission($role, 'users.create');
        $this->assertFalse($schoolAdmin->hasPermission('users.create'));

        $this->actingAs($schoolAdmin)->post(route('users.store'), [
            'name' => 'Denied User',
            'email' => 'denied@example.com',
            'role_id' => $this->roleId(Role::TEACHER),
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertForbidden();

        $this->addPermission($role, 'users.create');
        $this->assertTrue($schoolAdmin->hasPermission('users.create'));

        $this->actingAs($schoolAdmin)->post(route('users.store'), [
            'name' => 'Allowed User',
            'email' => 'allowed@example.com',
            'role_id' => $this->roleId(Role::TEACHER),
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'allowed@example.com',
            'school_id' => $school->id,
        ]);
    }

    public function test_current_dashboard_school_and_setting_policies_require_exact_permissions(): void
    {
        $school = $this->school('One');
        $superAdmin = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->removePermission($superAdmin->role, 'schools.create');
        $this->assertFalse($superAdmin->can('create', School::class));

        $context = app(TenantContext::class);
        $context->setTenant($school->id);
        $settings = SchoolSetting::create([]);
        $this->removePermission($schoolAdmin->role, 'school_settings.update');

        $this->assertTrue($schoolAdmin->can('view', $settings));
        $this->assertFalse($schoolAdmin->can('update', $settings));

        $this->removePermission($teacher->role, 'dashboard.view');
        $this->assertFalse($teacher->can('dashboard.view'));
        $context->clear();
    }

    public function test_role_assignment_requires_permission_and_retains_tenant_scope(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $teacherOne = $this->schoolUser(Role::TEACHER, $schoolOne, 'teacher-one@example.com');
        $teacherTwo = $this->schoolUser(Role::TEACHER, $schoolTwo, 'teacher-two@example.com');

        $this->removePermission($schoolAdmin->role, 'roles.assign');

        $payload = [
            'name' => $teacherOne->name,
            'email' => $teacherOne->email,
            'role_id' => $this->roleId(Role::ACCOUNTANT),
        ];

        $this->actingAs($schoolAdmin)
            ->put(route('users.update', $teacherOne), $payload)
            ->assertForbidden();
        $this->actingAs($schoolAdmin)
            ->put(route('users.update', $teacherTwo), $payload)
            ->assertForbidden();

        $this->assertSame($this->roleId(Role::TEACHER), $teacherOne->fresh()->role_id);
        $this->assertSame($this->roleId(Role::TEACHER), $teacherTwo->fresh()->role_id);
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'RBAC-'.$key,
            'email' => 'rbac-'.$key.'@example.com',
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

    private function roleId(string $code): int
    {
        return (int) Role::query()->where('code', $code)->value('id');
    }

    private function addPermission(Role $role, string $permissionCode): void
    {
        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $role->id,
                'permission_id' => Permission::query()->where('code', $permissionCode)->value('id'),
            ],
            ['created_at' => now(), 'updated_at' => now()],
        );
    }

    private function removePermission(Role $role, string $permissionCode): void
    {
        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', Permission::query()->where('code', $permissionCode)->value('id'))
            ->delete();
    }
}
