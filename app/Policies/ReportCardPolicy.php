<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;

class ReportCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canEstablishTenantContext($user)
            && filled($user->school_id)
            && $user->hasPermission('exams.view')
            && ($this->isSchoolAdmin($user) || $this->activeTeacherProfile($user) instanceof Teacher);
    }

    public function view(User $user, ReportCard $reportCard): bool
    {
        return $this->viewAny($user)
            && $this->canAccessTenant($user, $reportCard)
            && ($this->isSchoolAdmin($user) || $this->canTeacherAccessReportCard($user, $reportCard));
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

    private function canTeacherAccessReportCard(User $user, ReportCard $reportCard): bool
    {
        $teacher = $this->activeTeacherProfile($user);

        if (! $teacher instanceof Teacher) {
            return false;
        }

        return ExamResult::query()
            ->where('exam_id', $reportCard->exam_id)
            ->where('student_id', $reportCard->student_id)
            ->whereHas('examSubject.subject', fn ($query) => $query
                ->where('teacher_id', $teacher->id)
                ->where('class_id', $reportCard->class_id))
            ->exists();
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

    private function canAccessTenant(User $user, ReportCard|Exam $model): bool
    {
        return filled($user->school_id)
            && (int) $user->school_id === (int) $model->school_id;
    }
}
