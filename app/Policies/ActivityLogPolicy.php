<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasPermission('activity_logs.view')) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRoleCode(Role::SCHOOL_ADMIN);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->viewAny($user);
    }
}
