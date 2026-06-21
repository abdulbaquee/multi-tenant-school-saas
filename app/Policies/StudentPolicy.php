<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        if (! $user->canEstablishTenantContext() || ! $user->hasPermission('students.view')) {
            return false;
        }

        return $user->isSuperAdmin()
            || $this->isSchoolAdmin($user)
            || $this->activeTeacherProfile($user) instanceof Teacher;
    }

    public function view(User $user, Student $student): bool
    {
        if (! $this->viewAny($user) || ! $this->canAccessTenant($user, $student)) {
            return false;
        }

        if ($user->isSuperAdmin() || $this->isSchoolAdmin($user)) {
            return true;
        }

        $teacher = $this->activeTeacherProfile($user);

        return $teacher instanceof Teacher && $this->isAssignedStudent($student, $teacher);
    }

    public function create(User $user): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.create');
    }

    public function enroll(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.create')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }

    public function transfer(User $user, Student $student): bool
    {
        return $this->canApplyTerminalLifecycle($user, $student);
    }

    public function graduate(User $user, Student $student): bool
    {
        return $this->canApplyTerminalLifecycle($user, $student);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.update')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && in_array($student->status, [Student::STATUS_ACTIVE, Student::STATUS_INACTIVE], true);
    }

    public function activate(User $user, Student $student): bool
    {
        return $this->update($user, $student)
            && $student->status === Student::STATUS_INACTIVE;
    }

    public function deactivate(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.delete')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }

    public function archive(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.delete')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_INACTIVE;
    }

    public function restore(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.update')
            && $this->canAccessTenant($user, $student)
            && $student->trashed();
    }

    public function viewPhoto(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.view')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }

    public function updatePhoto(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.update')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return $user->hasRoleCode(Role::SCHOOL_ADMIN) && filled($user->school_id);
    }

    private function activeTeacherProfile(User $user): ?Teacher
    {
        if (! $user->hasRoleCode(Role::TEACHER) || ! filled($user->school_id)) {
            return null;
        }

        return $user->teacherProfile()
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
    }

    private function canAccessTenant(User $user, Student $student): bool
    {
        return $user->isSuperAdmin()
            || (filled($user->school_id)
                && (int) $user->school_id === (int) $student->school_id);
    }

    private function isAssignedStudent(Student $student, Teacher $teacher): bool
    {
        return ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE
            && $student->enrollments()
                ->where('status', StudentEnrollment::STATUS_ACTIVE)
                ->whereHas('schoolClass', fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', SchoolClass::STATUS_ACTIVE))
                ->whereHas('section', fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE))
                ->where(function ($query) use ($teacher): void {
                    $query->whereHas('section', fn ($query) => $query
                        ->whereNull('deleted_at')
                        ->where('status', Section::STATUS_ACTIVE)
                        ->where('teacher_id', $teacher->id))
                        ->orWhereHas('schoolClass.subjects', fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->where('status', Subject::STATUS_ACTIVE)
                            ->where('teacher_id', $teacher->id));
                })
                ->exists();
    }

    private function canApplyTerminalLifecycle(User $user, Student $student): bool
    {
        return $this->isSchoolAdmin($user)
            && $user->canEstablishTenantContext()
            && $user->hasPermission('students.update')
            && $this->canAccessTenant($user, $student)
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }
}
