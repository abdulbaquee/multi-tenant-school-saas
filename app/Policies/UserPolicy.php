<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers() && $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->canManageUsers()
            && $user->hasPermission('users.view')
            && $this->sameScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers()
            && $user->hasPermission('users.create')
            && $user->hasPermission('roles.assign');
    }

    public function update(User $user, User $model): bool
    {
        return $user->canManageUsers()
            && $user->hasPermission('users.update')
            && $this->sameScope($user, $model);
    }

    public function activate(User $user, User $model): bool
    {
        return $this->canChangeStatus($user, $model)
            && $user->hasPermission('users.update')
            && $model->status === User::STATUS_INACTIVE;
    }

    public function deactivate(User $user, User $model): bool
    {
        return $this->canChangeStatus($user, $model)
            && $user->hasPermission('users.delete')
            && $model->status === User::STATUS_ACTIVE;
    }

    private function sameScope(User $actor, User $subject): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if (! $actor->school_id || ! $subject->school_id) {
            return false;
        }

        return (int) $actor->school_id === (int) $subject->school_id;
    }

    private function canChangeStatus(User $actor, User $subject): bool
    {
        return $actor->canManageUsers()
            && $this->sameScope($actor, $subject)
            && ! $actor->is($subject)
            && ! $subject->isSuperAdmin();
    }
}
