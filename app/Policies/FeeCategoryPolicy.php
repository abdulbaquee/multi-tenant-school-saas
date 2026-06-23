<?php

namespace App\Policies;

use App\Models\FeeCategory;
use App\Models\Role;
use App\Models\User;

class FeeCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSchoolAdmin($user) && $user->hasPermission('fees.view');
    }

    public function view(User $user, FeeCategory $feeCategory): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $feeCategory);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.create');
    }

    public function update(User $user, FeeCategory $feeCategory): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.update')
            && $this->canAccessTenant($user, $feeCategory)
            && ! $feeCategory->trashed();
    }

    public function activate(User $user, FeeCategory $feeCategory): bool
    {
        return $this->update($user, $feeCategory)
            && $feeCategory->status === FeeCategory::STATUS_INACTIVE;
    }

    public function deactivate(User $user, FeeCategory $feeCategory): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('fees.delete')
            && $this->canAccessTenant($user, $feeCategory)
            && ! $feeCategory->trashed()
            && $feeCategory->status === FeeCategory::STATUS_ACTIVE;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessTenant(User $user, FeeCategory $feeCategory): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $feeCategory->school_id;
    }
}
