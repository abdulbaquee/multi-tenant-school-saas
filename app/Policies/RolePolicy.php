<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canEstablishTenantContext()
            && $user->hasPermission('roles.view')
            && ($user->isSuperAdmin() || $this->isSchoolAdmin($user));
    }

    public function view(User $user, Role $role): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->isSuperAdmin() || in_array($role->code, $this->schoolRoleCodes(), true);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermission('roles.manage')
            && in_array($role->code, $this->schoolRoleCodes(), true);
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    /**
     * @return list<string>
     */
    private function schoolRoleCodes(): array
    {
        return [Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT];
    }
}
