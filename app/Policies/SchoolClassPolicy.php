<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

class SchoolClassPolicy
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

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        if (! $this->viewAny($user) || ! $this->canAccessTenant($user, $schoolClass)) {
            return false;
        }

        if ($user->isSuperAdmin() || $this->isSchoolAdmin($user)) {
            return true;
        }

        $teacher = $this->activeTeacherProfile($user);

        return $teacher instanceof Teacher
            && ! $schoolClass->trashed()
            && $schoolClass->status === SchoolClass::STATUS_ACTIVE
            && ($schoolClass->sections()
                ->where('teacher_id', $teacher->id)
                ->where('status', Section::STATUS_ACTIVE)
                ->exists()
                || $schoolClass->subjects()
                    ->where('teacher_id', $teacher->id)
                    ->where('status', Subject::STATUS_ACTIVE)
                    ->exists());
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.create');
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessTenant($user, $schoolClass)
            && ! $schoolClass->trashed();
    }

    public function activate(User $user, SchoolClass $schoolClass): bool
    {
        return $this->update($user, $schoolClass)
            && $schoolClass->status === SchoolClass::STATUS_INACTIVE;
    }

    public function deactivate(User $user, SchoolClass $schoolClass): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessTenant($user, $schoolClass)
            && ! $schoolClass->trashed()
            && $schoolClass->status === SchoolClass::STATUS_ACTIVE;
    }

    public function archive(User $user, SchoolClass $schoolClass): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.delete')
            && $this->canAccessTenant($user, $schoolClass)
            && ! $schoolClass->trashed()
            && $schoolClass->status === SchoolClass::STATUS_INACTIVE;
    }

    public function restore(User $user, SchoolClass $schoolClass): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('academic.update')
            && $this->canAccessTenant($user, $schoolClass)
            && $schoolClass->trashed();
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

    private function canAccessTenant(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $schoolClass->school_id);
    }
}
