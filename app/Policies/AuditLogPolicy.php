<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasPermission('audit_logs.view')) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRoleCode(Role::SCHOOL_ADMIN);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user);
    }
}
