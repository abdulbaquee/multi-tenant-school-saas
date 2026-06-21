<?php

namespace App\Policies;

use App\Models\AcademicTerm;
use App\Models\Role;
use App\Models\User;

class AcademicTermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEstablishTenantContext()
            && $user->hasPermission('academic.view')
            && ($user->isSuperAdmin() || $this->isSchoolAdmin($user));
    }

    public function view(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->viewAny($user) && $this->canAccessRecord($user, $academicTerm);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.create');
    }

    public function update(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessRecord($user, $academicTerm);
    }

    public function reactivate(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->update($user, $academicTerm)
            && $academicTerm->status === AcademicTerm::STATUS_INACTIVE;
    }

    public function deactivate(User $user, AcademicTerm $academicTerm): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessRecord($user, $academicTerm)
            && $academicTerm->status === AcademicTerm::STATUS_ACTIVE;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessRecord(User $user, AcademicTerm $academicTerm): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $academicTerm->school_id);
    }
}
