<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        if (! $user->canEstablishTenantContext() || ! $user->hasPermission('academic.view')) {
            return false;
        }

        return $user->isSuperAdmin()
            || $this->isSchoolAdmin($user)
            || $this->activeTeacherProfile($user) instanceof Teacher;
    }

    public function view(User $user, Subject $subject): bool
    {
        if (! $this->viewAny($user) || ! $this->canAccessTenant($user, $subject)) {
            return false;
        }

        if ($user->isSuperAdmin() || $this->isSchoolAdmin($user)) {
            return true;
        }

        $teacher = $this->activeTeacherProfile($user);

        return $teacher instanceof Teacher
            && ! $subject->trashed()
            && $subject->status === Subject::STATUS_ACTIVE
            && (int) $subject->teacher_id === (int) $teacher->id
            && $subject->schoolClass()
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->whereNull('deleted_at')
                ->exists();
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.create');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessTenant($user, $subject)
            && ! $subject->trashed();
    }

    public function activate(User $user, Subject $subject): bool
    {
        return $this->update($user, $subject)
            && $subject->status === Subject::STATUS_INACTIVE;
    }

    public function deactivate(User $user, Subject $subject): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessTenant($user, $subject)
            && ! $subject->trashed()
            && $subject->status === Subject::STATUS_ACTIVE;
    }

    public function archive(User $user, Subject $subject): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessTenant($user, $subject)
            && ! $subject->trashed()
            && $subject->status === Subject::STATUS_INACTIVE;
    }

    public function restore(User $user, Subject $subject): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessTenant($user, $subject)
            && $subject->trashed();
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function activeTeacherProfile(User $user): ?Teacher
    {
        if (! $user->hasRoleCode(Role::TEACHER) || ! filled($user->school_id)) {
            return null;
        }

        return $user->teacherProfile()
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
    }

    private function canAccessTenant(User $user, Subject $subject): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $subject->school_id);
    }
}
