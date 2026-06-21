<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Http\FormRequest;

class StudentEnrollmentLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->route()?->getName()) {
            'student-enrollments.complete' => $this->authorizeEnrollment('complete'),
            'students.transfer' => $this->authorizeStudent('transfer'),
            'students.graduate' => $this->authorizeStudent('graduate'),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'student_id' => ['prohibited'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'section_id' => ['prohibited'],
            'roll_no' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    private function authorizeEnrollment(string $ability): bool
    {
        $enrollment = $this->route('student_enrollment');

        return $enrollment instanceof StudentEnrollment
            && ($this->user()?->can($ability, $enrollment) ?? false);
    }

    private function authorizeStudent(string $ability): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can($ability, $student) ?? false);
    }
}
