<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentEnrollmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can('enroll', $student) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'roll_no' => trim((string) $this->input('roll_no')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'min:1'],
            'class_id' => ['required', 'integer', 'min:1'],
            'section_id' => ['required', 'integer', 'min:1'],
            'roll_no' => ['required', 'string', 'max:50'],
            'enrollment_date' => ['required', 'date', 'before_or_equal:today'],
            'student_id' => ['prohibited'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
