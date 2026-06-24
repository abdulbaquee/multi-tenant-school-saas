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

    public function collectAny(User $user): bool
    {
        return $this->isFeeOperator($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.collect');
    }

    public function collect(User $user, StudentFee $studentFee): bool
    {
        return $this->collectAny($user)
            && $this->canAccessTenant($user, $studentFee)
            && $this->isCollectible($studentFee);
    }

    public function viewOutstandingAny(User $user): bool
    {
        return $this->isFeeOperator($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.view');
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function isFeeOperator(User $user): bool
    {
        return filled($user->school_id)
            && ($user->hasRoleCode(Role::SCHOOL_ADMIN) || $user->hasRoleCode(Role::ACCOUNTANT));
    }

    private function canAccessTenant(User $user, StudentFee $studentFee): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $studentFee->school_id;
    }

    private function isCollectible(StudentFee $studentFee): bool
    {
        if (! in_array($studentFee->status, [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL], true)) {
            return false;
        }

        return bccomp((string) $studentFee->balance_amount, '0.00', 2) === 1;
    }
}
