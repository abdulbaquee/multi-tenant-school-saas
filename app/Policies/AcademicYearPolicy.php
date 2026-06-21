<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEstablishTenantContext()
            && $user->hasPermission('academic.view')
            && ($user->isSuperAdmin() || $this->isSchoolAdmin($user));
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->viewAny($user) && $this->canAccessRecord($user, $academicYear);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.create');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessRecord($user, $academicYear);
    }

    public function activate(User $user, AcademicYear $academicYear): bool
    {
        return $this->update($user, $academicYear)
            && $academicYear->status === AcademicYear::STATUS_ACTIVE
            && ! $academicYear->is_current;
    }

    public function reactivate(User $user, AcademicYear $academicYear): bool
    {
        return $this->update($user, $academicYear)
            && $academicYear->status === AcademicYear::STATUS_INACTIVE;
    }

    public function deactivate(User $user, AcademicYear $academicYear): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessRecord($user, $academicYear)
            && $academicYear->status === AcademicYear::STATUS_ACTIVE
            && ! $academicYear->is_current;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessRecord(User $user, AcademicYear $academicYear): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $academicYear->school_id);
    }
}
