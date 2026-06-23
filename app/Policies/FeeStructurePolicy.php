<?php

namespace App\Policies;

use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\User;

class FeeStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSchoolAdmin($user) && $user->hasPermission('fees.view');
    }

    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $feeStructure);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.create');
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.update')
            && $this->canAccessTenant($user, $feeStructure)
            && ! $feeStructure->trashed();
    }

    public function activate(User $user, FeeStructure $feeStructure): bool
    {
        return $this->update($user, $feeStructure)
            && $feeStructure->status === FeeStructure::STATUS_INACTIVE;
    }

    public function deactivate(User $user, FeeStructure $feeStructure): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.delete')
            && $this->canAccessTenant($user, $feeStructure)
            && ! $feeStructure->trashed()
            && $feeStructure->status === FeeStructure::STATUS_ACTIVE;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessTenant(User $user, FeeStructure $feeStructure): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $feeStructure->school_id;
    }
}
