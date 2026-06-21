<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEstablishTenantContext()
            && $user->hasPermission('academic.view')
            && ($user->isSuperAdmin() || $this->isSchoolAdmin($user));
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->viewAny($user) && $this->canAccessRecord($user, $teacher);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.create');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessRecord($user, $teacher)
            && ! $teacher->trashed();
    }

    public function activate(User $user, Teacher $teacher): bool
    {
        return $this->update($user, $teacher)
            && $teacher->status === Teacher::STATUS_INACTIVE;
    }

    public function deactivate(User $user, Teacher $teacher): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessRecord($user, $teacher)
            && ! $teacher->trashed()
            && $teacher->status === Teacher::STATUS_ACTIVE;
    }

    public function archive(User $user, Teacher $teacher): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessRecord($user, $teacher)
            && ! $teacher->trashed()
            && $teacher->status === Teacher::STATUS_INACTIVE;
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessRecord($user, $teacher)
            && $teacher->trashed();
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessRecord(User $user, Teacher $teacher): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $teacher->school_id);
    }
}
