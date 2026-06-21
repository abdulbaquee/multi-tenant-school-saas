<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher_profile');

        return $teacher instanceof Teacher
            && ($this->user()?->can('update', $teacher) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_code' => Str::upper(trim((string) $this->input('employee_code'))),
            'qualification' => $this->nullableTrimmed('qualification'),
            'specialization' => $this->nullableTrimmed('specialization'),
            'phone' => $this->nullableTrimmed('phone'),
            'joining_date' => $this->nullableTrimmed('joining_date'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Teacher $teacher */
        $teacher = $this->route('teacher_profile');

        return [
            'employee_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teachers', 'employee_code')
                    ->where('school_id', $this->user()?->school_id)
                    ->ignore($teacher->id),
            ],
            'qualification' => ['nullable', 'string', 'max:150'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'user_id' => ['prohibited'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
