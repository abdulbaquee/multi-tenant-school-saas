<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        $roles = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Platform administrator with authorized system-wide access.',
            ],
            'school_admin' => [
                'name' => 'School Admin',
                'description' => 'School administrator with access to own school operations.',
            ],
            'teacher' => [
                'name' => 'Teacher',
                'description' => 'Teacher with access to assigned academic workflows.',
            ],
            'accountant' => [
                'name' => 'Accountant',
                'description' => 'Accountant with access to fee and financial workflows.',
            ],
        ];

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

        $permissions = [
            ['profile.view', 'Profile View', 'Authentication & Profile'],
            ['profile.update', 'Profile Update', 'Authentication & Profile'],
            ['dashboard.view', 'Dashboard View', 'Dashboard & Analytics'],
            ['analytics.view', 'Analytics View', 'Dashboard & Analytics'],
            ['schools.view', 'School View', 'School Management'],
            ['schools.create', 'School Create', 'School Management'],
            ['schools.update', 'School Update', 'School Management'],
            ['schools.delete', 'School Delete', 'School Management'],
            ['schools.export', 'School Export', 'School Management'],
            ['school_settings.view', 'School Settings View', 'School Settings'],
            ['school_settings.update', 'School Settings Update', 'School Settings'],
            ['users.view', 'User View', 'User Management'],
            ['users.create', 'User Create', 'User Management'],
            ['users.update', 'User Update', 'User Management'],
            ['users.delete', 'User Delete', 'User Management'],
            ['users.export', 'User Export', 'User Management'],
            ['roles.view', 'Role View', 'Role & Permission'],
            ['roles.assign', 'Role Assign', 'Role & Permission'],
            ['roles.manage', 'Role Manage', 'Role & Permission'],
            ['academic.view', 'Academic Structure View', 'Academic Structure'],
            ['academic.create', 'Academic Structure Create', 'Academic Structure'],
            ['academic.update', 'Academic Structure Update', 'Academic Structure'],
            ['academic.delete', 'Academic Structure Delete', 'Academic Structure'],
            ['academic.export', 'Academic Structure Export', 'Academic Structure'],
            ['students.view', 'Student View', 'Student Management'],
            ['students.create', 'Student Create', 'Student Management'],
            ['students.update', 'Student Update', 'Student Management'],
            ['students.delete', 'Student Delete', 'Student Management'],
            ['students.export', 'Student Export', 'Student Management'],
            ['attendance.view', 'Attendance View', 'Attendance Management'],
            ['attendance.create', 'Attendance Create', 'Attendance Management'],
            ['attendance.update', 'Attendance Update', 'Attendance Management'],
            ['attendance.delete', 'Attendance Delete', 'Attendance Management'],
            ['attendance.report', 'Attendance Report', 'Attendance Management'],
            ['attendance.export', 'Attendance Export', 'Attendance Management'],
            ['fees.view', 'Fee View', 'Fee Management'],
            ['fees.create', 'Fee Create', 'Fee Management'],
            ['fees.update', 'Fee Update', 'Fee Management'],
            ['fees.delete', 'Fee Delete', 'Fee Management'],
            ['fees.collect', 'Fee Collect', 'Fee Management'],
            ['fees.report', 'Fee Report', 'Fee Management'],
            ['fees.export', 'Fee Export', 'Fee Management'],
            ['exams.view', 'Examination View', 'Examination Management'],
            ['exams.create', 'Examination Create', 'Examination Management'],
            ['exams.update', 'Examination Update', 'Examination Management'],
            ['exams.delete', 'Examination Delete', 'Examination Management'],
            ['exams.publish', 'Examination Publish', 'Examination Management'],
            ['exams.report', 'Examination Report', 'Examination Management'],
            ['exams.export', 'Examination Export', 'Examination Management'],
            ['reports.view', 'Report View', 'Reporting'],
            ['reports.export', 'Report Export', 'Reporting'],
            ['activity_logs.view', 'Activity Log View', 'Activity Logs'],
            ['audit_logs.view', 'Audit Log View', 'Audit Trail'],
            ['backups.view', 'Backup View', 'Backup Management'],
            ['backups.create', 'Backup Create', 'Backup Management'],
            ['backups.download', 'Backup Download', 'Backup Management'],
            ['backups.delete', 'Backup Delete', 'Backup Management'],
        ];

        foreach ($permissions as [$code, $name, $module]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'module' => $module,
                    'description' => $name.' permission.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $roleIds = DB::table('roles')->pluck('id', 'code');
        $permissionIds = DB::table('permissions')->pluck('id', 'code');

        $rolePermissions = [
            'super_admin' => array_keys($permissionIds->all()),
            'school_admin' => [
                'profile.view',
                'profile.update',
                'dashboard.view',
                'analytics.view',
                'school_settings.view',
                'school_settings.update',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.export',
                'roles.view',
                'roles.assign',
                'academic.view',
                'academic.create',
                'academic.update',
                'academic.delete',
                'academic.export',
                'students.view',
                'students.create',
                'students.update',
                'students.delete',
                'students.export',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'attendance.delete',
                'attendance.report',
                'attendance.export',
                'fees.view',
                'fees.create',
                'fees.update',
                'fees.delete',
                'fees.collect',
                'fees.report',
                'fees.export',
                'exams.view',
                'exams.create',
                'exams.update',
                'exams.delete',
                'exams.publish',
                'exams.report',
                'exams.export',
                'reports.view',
                'reports.export',
                'activity_logs.view',
                'audit_logs.view',
            ],
            'teacher' => [
                'profile.view',
                'profile.update',
                'dashboard.view',
                'analytics.view',
                'academic.view',
                'students.view',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'attendance.delete',
                'attendance.report',
                'attendance.export',
                'exams.view',
                'exams.create',
                'exams.update',
                'exams.delete',
                'exams.publish',
                'exams.report',
                'exams.export',
                'reports.view',
                'reports.export',
            ],
            'accountant' => [
                'profile.view',
                'profile.update',
                'dashboard.view',
                'analytics.view',
                'students.view',
                'fees.view',
                'fees.create',
                'fees.update',
                'fees.delete',
                'fees.collect',
                'fees.report',
                'fees.export',
                'reports.view',
                'reports.export',
            ],
        ];

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleIds[$roleCode],
                        'permission_id' => $permissionIds[$permissionCode],
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'superadmin@example.com'],
            [
                'school_id' => null,
                'role_id' => $roleIds['super_admin'],
                'name' => 'Super Admin',
                'email_verified_at' => $now,
                'password' => Hash::make('password'),
                'phone' => null,
                'status' => 'active',
                'last_login_at' => null,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        );
    }
}
