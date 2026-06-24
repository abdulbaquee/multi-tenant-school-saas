<?php

namespace App\Policies;

use App\Models\BackupLog;
use App\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('backups.view');
    }

    public function view(User $user, BackupLog $backupLog): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('backups.create');
    }

    public function download(User $user, BackupLog $backupLog): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermission('backups.download')
            && $backupLog->isDownloadable();
    }

    public function delete(User $user, BackupLog $backupLog): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermission('backups.delete')
            && $backupLog->isDeletable();
    }
}
