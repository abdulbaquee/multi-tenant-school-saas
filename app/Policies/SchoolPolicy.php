<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('schools.view');
    }

    public function view(User $user, School $school): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('schools.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('schools.create');
    }

    public function update(User $user, School $school): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('schools.update');
    }

    public function activate(User $user, School $school): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermission('schools.update')
            && $school->status === School::STATUS_INACTIVE;
    }

    public function deactivate(User $user, School $school): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermission('schools.delete')
            && $school->status === School::STATUS_ACTIVE;
    }
}
