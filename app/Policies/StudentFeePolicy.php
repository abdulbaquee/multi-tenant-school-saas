<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\StudentFee;
use App\Models\User;

class StudentFeePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSchoolAdmin($user) && $user->hasPermission('fees.view');
    }

    public function view(User $user, StudentFee $studentFee): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $studentFee);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.create');
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessTenant(User $user, StudentFee $studentFee): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $studentFee->school_id;
    }
}
