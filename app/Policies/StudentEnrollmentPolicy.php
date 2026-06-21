<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function complete(User $user, StudentEnrollment $enrollment): bool
    {
        if (! $user->hasRoleCode(Role::SCHOOL_ADMIN)
            || ! $user->canEstablishTenantContext()
            || ! $user->hasPermission('students.update')
            || blank($user->school_id)
            || (int) $user->school_id !== (int) $enrollment->school_id
            || $enrollment->status !== StudentEnrollment::STATUS_ACTIVE) {
            return false;
        }

        $student = $enrollment->relationLoaded('student')
            ? $enrollment->getRelation('student')
            : $enrollment->student()->first();

        return $student instanceof Student
            && ! $student->trashed()
            && $student->status === Student::STATUS_ACTIVE;
    }
}
