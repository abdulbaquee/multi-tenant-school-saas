<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /** @var array<string, array{name: string, description: string}> $roles */
        $roles = config('rbac.roles');
        /** @var array<string, array{name: string, module: string}> $permissions */
        $permissions = config('rbac.permissions');
        /** @var array<string, list<string>> $maximumMappings */
        $maximumMappings = config('rbac.maximum_mappings');
        /** @var array<string, list<string>> $defaultMappings */
        $defaultMappings = config('rbac.default_mappings');
        /** @var array<string, list<string>> $essentialPermissions */
        $essentialPermissions = config('rbac.essential_permissions');
        /** @var array{name: string, email: string, password: ?string} $bootstrapSuperAdmin */
        $bootstrapSuperAdmin = config('rbac.bootstrap_super_admin');

        DB::transaction(function () use (
            $roles,
            $permissions,
            $maximumMappings,
            $defaultMappings,
            $essentialPermissions,
            $bootstrapSuperAdmin,
        ): void {
            $now = now();

            foreach ($roles as $code => $role) {
                DB::table('roles')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $role['name'],
                        'description' => $role['description'],
                        'is_system' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            foreach ($permissions as $code => $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $permission['name'],
                        'module' => $permission['module'],
                        'description' => $permission['name'].' permission.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            $roleIds = DB::table('roles')->pluck('id', 'code');
            $permissionIds = DB::table('permissions')->pluck('id', 'code');

            foreach (array_keys($roles) as $roleCode) {
                $roleId = (int) $roleIds[$roleCode];
                $currentCodes = DB::table('role_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                    ->where('role_permissions.role_id', $roleId)
                    ->pluck('permissions.code')
                    ->all();

                $desiredCodes = $roleCode === 'super_admin' || $currentCodes === []
                    ? $defaultMappings[$roleCode]
                    : array_values(array_unique([
                        ...array_intersect($currentCodes, $maximumMappings[$roleCode]),
                        ...$essentialPermissions[$roleCode],
                    ]));

                DB::table('role_permissions')->where('role_id', $roleId)->delete();

                DB::table('role_permissions')->insert(array_map(
                    fn (string $permissionCode): array => [
                        'role_id' => $roleId,
                        'permission_id' => (int) $permissionIds[$permissionCode],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $desiredCodes,
                ));
            }

            $superAdminRoleId = (int) $roleIds['super_admin'];
            $existingSuperAdminId = DB::table('users')
                ->where('email', $bootstrapSuperAdmin['email'])
                ->value('id');

            if ($existingSuperAdminId) {
                DB::table('users')
                    ->where('id', $existingSuperAdminId)
                    ->update([
                        'school_id' => null,
                        'role_id' => $superAdminRoleId,
                        'updated_at' => $now,
                    ]);

                return;
            }

            $bootstrapPassword = $bootstrapSuperAdmin['password'];

            if (! is_string($bootstrapPassword)
                || strlen($bootstrapPassword) < 8
                || ! preg_match('/[a-z]/', $bootstrapPassword)
                || ! preg_match('/[A-Z]/', $bootstrapPassword)
                || ! preg_match('/[0-9]/', $bootstrapPassword)) {
                throw new RuntimeException(
                    'SUPER_ADMIN_PASSWORD must contain at least eight characters, mixed case, and a number.',
                );
            }

            DB::table('users')->insert([
                'school_id' => null,
                'role_id' => $superAdminRoleId,
                'name' => $bootstrapSuperAdmin['name'],
                'email' => $bootstrapSuperAdmin['email'],
                'email_verified_at' => $now,
                'password' => Hash::make($bootstrapPassword),
                'phone' => null,
                'status' => 'active',
                'last_login_at' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        });

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
