<?php

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;

class PaymentTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isFeeOperator($user) && $user->hasPermission('fees.view');
    }

    public function view(User $user, PaymentTransaction $paymentTransaction): bool
    {
        return $this->viewAny($user)
            && $this->canAccessTenant($user, $paymentTransaction)
            && $paymentTransaction->payment_mode === PaymentTransaction::MODE_SANDBOX_GATEWAY;
    }

    private function isFeeOperator(User $user): bool
    {
        return filled($user->school_id)
            && ($user->hasRoleCode(Role::SCHOOL_ADMIN) || $user->hasRoleCode(Role::ACCOUNTANT));
    }

    private function canAccessTenant(User $user, PaymentTransaction $paymentTransaction): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $paymentTransaction->school_id;
    }
}
