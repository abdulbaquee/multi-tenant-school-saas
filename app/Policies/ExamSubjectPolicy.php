<?php

namespace App\Policies;

use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\User;

class ExamSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSchoolAdmin($user) && $user->hasPermission('exams.view');
    }

    public function view(User $user, ExamSubject $examSubject): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $examSubject);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.create');
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessTenant(User $user, ExamSubject $examSubject): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $examSubject->school_id;
    }
}
