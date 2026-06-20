<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, School $school): bool
    {
        return $user->isSuperAdmin();
    }

    public function activate(User $user, School $school): bool
    {
        return $user->isSuperAdmin()
            && $school->status === School::STATUS_INACTIVE;
    }

    public function deactivate(User $user, School $school): bool
    {
        return $user->isSuperAdmin()
            && $school->status === School::STATUS_ACTIVE;
    }
}
