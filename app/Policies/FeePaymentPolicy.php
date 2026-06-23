<?php

namespace App\Policies;

use App\Models\FeePayment;
use App\Models\Role;
use App\Models\User;

class FeePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isFeeOperator($user) && $user->hasPermission('fees.view');
    }

    public function view(User $user, FeePayment $feePayment): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $feePayment);
    }

    private function isFeeOperator(User $user): bool
    {
        return filled($user->school_id)
            && ($user->hasRoleCode(Role::SCHOOL_ADMIN) || $user->hasRoleCode(Role::ACCOUNTANT));
    }

    private function canAccessTenant(User $user, FeePayment $feePayment): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $feePayment->school_id;
    }
}
