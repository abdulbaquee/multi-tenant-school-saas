<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canEstablishTenantContext($user)
            && filled($user->school_id)
            && $this->hasPermission($user, 'attendance.view')
            && ($this->isSchoolAdmin($user) || $this->activeTeacherProfile($user) instanceof Teacher);
    }

    public function saveRoster(User $user): bool
    {
        return $this->viewAny($user)
            && ($this->hasPermission($user, 'attendance.create')
                || $this->hasPermission($user, 'attendance.update'));
    }

    public function operate(User $user, Section $section): bool
    {
        if (! $this->viewAny($user)
            || blank($user->school_id)
            || (int) $user->school_id !== (int) $section->school_id
            || $section->trashed()
            || $section->status !== Section::STATUS_ACTIVE
            || ! $this->hasActiveClass($section)) {
            return false;
        }

        if ($this->isSchoolAdmin($user)) {
            return true;
        }

        $teacher = $this->activeTeacherProfile($user);

        return $teacher instanceof Teacher
            && (int) $section->teacher_id === (int) $teacher->id;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if (! $this->viewAny($user)
            || ! $this->hasPermission($user, 'attendance.update')
            || blank($user->school_id)
            || (int) $user->school_id !== (int) $attendance->school_id) {
            return false;
        }

        $academicYear = $attendance->relationLoaded('academicYear')
            ? $attendance->getRelation('academicYear')
            : $attendance->academicYear()->first();

        if (! $academicYear instanceof AcademicYear
            || ! $academicYear->is_current
            || $academicYear->status !== AcademicYear::STATUS_ACTIVE) {
            return false;
        }

        if ($this->isSchoolAdmin($user)) {
            return true;
        }

        if ($attendance->status === Attendance::STATUS_HOLIDAY) {
            return false;
        }

        $section = $attendance->relationLoaded('section')
            ? $attendance->getRelation('section')
            : $attendance->section()->first();

        return $section instanceof Section && $this->operate($user, $section);
    }

    public function markHoliday(User $user): bool
    {
        return $this->viewAny($user)
            && $this->isSchoolAdmin($user)
            && $this->hasPermission($user, 'attendance.create');
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN);
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

    private function canEstablishTenantContext(User $user): bool
    {
        if (! $user->relationLoaded('role') || ! $user->relationLoaded('school')) {
            return $user->canEstablishTenantContext();
        }

        $school = $user->getRelation('school');

        return $user->status === User::STATUS_ACTIVE
            && filled($user->school_id)
            && in_array($user->role?->code, [Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT], true)
            && $school instanceof School
            && ! $school->trashed()
            && $school->status === School::STATUS_ACTIVE;
    }

    private function hasPermission(User $user, string $code): bool
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        $role = $user->relationLoaded('role') ? $user->getRelation('role') : null;

        if ($role instanceof Role && $role->relationLoaded('permissions')) {
            return $role->permissions->contains('code', $code);
        }

        return $user->hasPermission($code);
    }

    private function hasActiveClass(Section $section): bool
    {
        if ($section->relationLoaded('schoolClass')) {
            $schoolClass = $section->getRelation('schoolClass');

            return $schoolClass instanceof SchoolClass
                && ! $schoolClass->trashed()
                && $schoolClass->status === SchoolClass::STATUS_ACTIVE;
        }

        return $section->schoolClass()
            ->whereNull('deleted_at')
            ->where('status', SchoolClass::STATUS_ACTIVE)
            ->exists();
    }
}
