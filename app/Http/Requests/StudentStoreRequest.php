<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'admission_no' => trim((string) $this->input('admission_no')),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => $this->nullableTrimmed('last_name'),
            'guardian_name' => trim((string) $this->input('guardian_name')),
            'guardian_phone' => trim((string) $this->input('guardian_phone')),
            'guardian_email' => $this->nullableTrimmed('guardian_email'),
            'address' => $this->nullableTrimmed('address'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'admission_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'admission_no')->where('school_id', $this->user()?->school_id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'gender' => [
                'required',
                Rule::in([
                    Student::GENDER_MALE,
                    Student::GENDER_FEMALE,
                    Student::GENDER_OTHER,
                    Student::GENDER_PREFER_NOT_TO_SAY,
                ]),
            ],
            'date_of_birth' => ['required', 'date', 'before:today', 'before:admission_date'],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'admission_date' => ['required', 'date', 'before_or_equal:today'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'photo_path' => ['prohibited'],
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
