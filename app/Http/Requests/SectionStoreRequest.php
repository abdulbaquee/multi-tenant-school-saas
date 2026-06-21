<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Section::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'teacher_id' => filled($this->input('teacher_id')) ? $this->input('teacher_id') : null,
            'capacity' => filled($this->input('capacity')) ? $this->input('capacity') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'class_id' => ['required', 'integer', Rule::exists(SchoolClass::class, 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'))],
            'teacher_id' => ['nullable', 'integer', Rule::exists(Teacher::class, 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', Teacher::STATUS_ACTIVE)->whereNull('deleted_at'))],
            'name' => ['required', 'string', 'max:50', Rule::unique('sections', 'name')->where(fn ($query) => $query->where('class_id', $this->input('class_id')))],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
