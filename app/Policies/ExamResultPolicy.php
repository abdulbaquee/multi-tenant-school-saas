<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

class ExamResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canEstablishTenantContext($user)
            && filled($user->school_id)
            && $user->hasPermission('exams.view')
            && ($this->isSchoolAdmin($user) || $this->activeTeacherProfile($user) instanceof Teacher);
    }

    public function view(User $user, ExamResult $examResult): bool
    {
        return $this->viewAny($user)
            && $this->canAccessTenant($user, $examResult)
            && ($this->isSchoolAdmin($user) || $this->canTeacherAccessExamSubject($user, $examResult->examSubject));
    }

    public function saveRoster(User $user): bool
    {
        return $this->viewAny($user)
            && ($user->hasPermission('exams.create') || $user->hasPermission('exams.update'));
    }

    public function operate(User $user, ExamSubject $examSubject): bool
    {
        if (! $this->saveRoster($user)
            || blank($user->school_id)
            || (int) $user->school_id !== (int) $examSubject->school_id) {
            return false;
        }

        $exam = $examSubject->relationLoaded('exam') ? $examSubject->exam : $examSubject->exam()->first();

        if (! $exam instanceof Exam
            || $exam->trashed()
            || $exam->status !== Exam::STATUS_ONGOING) {
            return false;
        }

        if ($this->isSchoolAdmin($user)) {
            return true;
        }

        return $this->canTeacherAccessExamSubject($user, $examSubject);
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

    private function canTeacherAccessExamSubject(User $user, ExamSubject $examSubject): bool
    {
        $teacher = $this->activeTeacherProfile($user);

        if (! $teacher instanceof Teacher) {
            return false;
        }

        $subject = $examSubject->relationLoaded('subject')
            ? $examSubject->subject
            : $examSubject->subject()->first();

        return $subject instanceof Subject
            && ! $subject->trashed()
            && $subject->status === Subject::STATUS_ACTIVE
            && (int) $subject->teacher_id === (int) $teacher->id
            && (int) $subject->class_id === (int) $examSubject->class_id;
    }

    private function activeTeacherProfile(User $user): ?Teacher
    {
        if (! $user->hasRoleCode(Role::TEACHER) || ! filled($user->school_id)) {
            return null;
        }

        if ($user->relationLoaded('teacherProfile')) {
            $teacher = $user->getRelation('teacherProfile');

            return $teacher instanceof Teacher
                && ! $teacher->trashed()
                && $teacher->status === Teacher::STATUS_ACTIVE
                    ? $teacher
                    : null;
        }

        return $user->teacherProfile()
            ->whereNull('deleted_at')
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function canEstablishTenantContext(User $user): bool
    {
        if (! $user->relationLoaded('role') || ! $user->relationLoaded('school')) {
            return $user->canEstablishTenantContext();
        }

        $school = $user->getRelation('school');

        return $user->status === User::STATUS_ACTIVE
            && filled($user->school_id)
            && in_array($user->role?->code, [Role::SCHOOL_ADMIN, Role::TEACHER], true)
            && $school instanceof School
            && ! $school->trashed()
            && $school->status === School::STATUS_ACTIVE;
    }

    private function canAccessTenant(User $user, ExamResult|Exam $model): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $model->school_id;
    }
}
