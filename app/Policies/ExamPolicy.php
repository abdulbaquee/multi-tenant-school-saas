<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Role;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSchoolAdmin($user) && $user->hasPermission('exams.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        return $this->viewAny($user) && $this->canAccessTenant($user, $exam);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.create');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.update')
            && $this->canAccessTenant($user, $exam)
            && ! $exam->trashed();
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam)
            && $user->hasPermission('exams.publish')
            && $exam->status === Exam::STATUS_SCHEDULED;
    }

    public function complete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam)
            && $user->hasPermission('exams.publish')
            && $exam->status === Exam::STATUS_ONGOING;
    }

    public function cancel(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam)
            && $user->hasPermission('exams.publish')
            && in_array($exam->status, [Exam::STATUS_SCHEDULED, Exam::STATUS_ONGOING], true);
    }

    public function archive(User $user, Exam $exam): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.delete')
            && $this->canAccessTenant($user, $exam)
            && ! $exam->trashed()
            && in_array($exam->status, [Exam::STATUS_SCHEDULED, Exam::STATUS_CANCELLED], true);
    }

    public function process(User $user, Exam $exam): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.update')
            && $this->canAccessTenant($user, $exam)
            && ! $exam->trashed()
            && $exam->status === Exam::STATUS_ONGOING;
    }

    public function generate(User $user, Exam $exam): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('exams.update')
            && $this->canAccessTenant($user, $exam)
            && ! $exam->trashed()
            && in_array($exam->status, [Exam::STATUS_ONGOING, Exam::STATUS_COMPLETED], true);
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canAccessTenant(User $user, Exam $exam): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $exam->school_id;
    }
}
