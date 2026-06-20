<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\User;

class SchoolSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN)
            && filled($user->school_id)
            && $user->hasPermission('school_settings.view');
    }

    public function view(User $user, SchoolSetting $schoolSetting): bool
    {
        return $this->viewAny($user)
            && (int) $user->school_id === (int) $schoolSetting->school_id;
    }

    public function update(User $user, SchoolSetting $schoolSetting): bool
    {
        return $this->view($user, $schoolSetting)
            && $user->hasPermission('school_settings.update');
    }
}
