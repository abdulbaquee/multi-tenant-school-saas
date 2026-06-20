<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SchoolSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SchoolSetting::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'school_name' => trim((string) $this->input('school_name')),
            'school_email' => Str::lower(trim((string) $this->input('school_email'))),
            'country' => trim((string) $this->input('country', 'India')),
            'timezone' => trim((string) $this->input('timezone', 'Asia/Kolkata')),
            'currency' => Str::upper(trim((string) $this->input('currency', 'INR'))),
            'grading_system' => Str::lower(trim((string) $this->input('grading_system', 'percentage'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'school_name' => ['required', 'string', 'max:150'],
            'school_email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('schools', 'email')->ignore($schoolId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'timezone' => ['required', 'timezone', 'max:80'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'academic_year_start_month' => ['required', 'integer', 'between:1,12'],
            'attendance_start_time' => ['nullable', 'date_format:H:i'],
            'grading_system' => ['required', Rule::in(['percentage', 'letter', 'gpa'])],
            'logo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'remove_logo' => ['sometimes', 'boolean'],
            'school_id' => ['prohibited'],
            'logo_path' => ['prohibited'],
            'settings_json' => ['prohibited'],
            'code' => ['prohibited'],
            'status' => ['prohibited'],
            'deactivated_at' => ['prohibited'],
            'deactivation_reason' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hasFile('logo') && $this->boolean('remove_logo')) {
                    $validator->errors()->add('logo', 'Upload a new logo or remove the current logo, not both.');
                }

                $school = $this->user()?->school;

                if (! $school instanceof School || $school->status !== School::STATUS_ACTIVE) {
                    $validator->errors()->add('school_name', 'An active school is required.');
                }
            },
        ];
    }
}
