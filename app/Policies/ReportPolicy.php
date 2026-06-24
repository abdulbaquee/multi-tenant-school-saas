<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Reporting\ReportCategory;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasPermission('reports.view')) {
            return false;
        }

        return $user->isSuperAdmin() || $user->canEstablishTenantContext();
    }

    public function exportAny(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasPermission('reports.export')) {
            return false;
        }

        return $user->isSuperAdmin() || $user->canEstablishTenantContext();
    }

    public function viewCategory(User $user, ReportCategory $category): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return match ($category) {
            ReportCategory::Students => $this->canViewStudentReports($user),
            ReportCategory::Attendance => $this->canViewAttendanceReports($user),
            ReportCategory::Fees => $this->canViewFeeReports($user),
            ReportCategory::Examinations => $this->canViewExaminationReports($user),
            ReportCategory::Schools => $user->isSuperAdmin(),
            ReportCategory::Users => $user->isSuperAdmin() || $user->hasRoleCode(Role::SCHOOL_ADMIN),
        };
    }

    public function exportCategory(User $user, ReportCategory $category): bool
    {
        if (! $this->exportAny($user) || ! $this->viewCategory($user, $category)) {
            return false;
        }

        return match ($category) {
            ReportCategory::Students => $user->hasPermission('students.export') || $user->hasPermission('reports.export'),
            ReportCategory::Attendance => $user->hasPermission('attendance.export') || ($user->isSuperAdmin() && $user->hasPermission('reports.export')),
            ReportCategory::Fees => $user->hasPermission('fees.export') || ($user->isSuperAdmin() && $user->hasPermission('reports.export')),
            ReportCategory::Examinations => $user->hasPermission('exams.export') || ($user->hasRoleCode(Role::TEACHER) && $user->hasPermission('reports.export')) || ($user->isSuperAdmin() && $user->hasPermission('reports.export')),
            ReportCategory::Schools, ReportCategory::Users => $user->isSuperAdmin() && $user->hasPermission('reports.export'),
        };
    }

    private function canViewStudentReports(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('students.view')) {
            return false;
        }

        return $user->hasRoleCode(Role::SCHOOL_ADMIN)
            || $user->hasRoleCode(Role::TEACHER)
            || $user->hasRoleCode(Role::ACCOUNTANT);
    }

    private function canViewAttendanceReports(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return $user->hasPermission('attendance.report') || $user->hasPermission('reports.view');
        }

        return $user->hasPermission('attendance.report');
    }

    private function canViewFeeReports(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return $user->hasPermission('fees.report') || $user->hasPermission('reports.view');
        }

        return $user->hasPermission('fees.report');
    }

    private function canViewExaminationReports(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return $user->hasPermission('exams.report') || $user->hasPermission('reports.view');
        }

        if ($user->hasRoleCode(Role::TEACHER)) {
            return $user->hasPermission('reports.view');
        }

        return $user->hasPermission('exams.report');
    }
}
