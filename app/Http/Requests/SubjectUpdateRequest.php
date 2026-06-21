<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $subject instanceof Subject
            && ($this->user()?->can('update', $subject) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => Str::upper(trim((string) $this->input('code'))),
            'teacher_id' => filled($this->input('teacher_id')) ? $this->input('teacher_id') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Subject $subject */
        $subject = $this->route('subject');
        $schoolId = $this->user()?->school_id;

        return [
            'class_id' => ['required', 'integer', Rule::exists(SchoolClass::class, 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'))],
            'teacher_id' => ['nullable', 'integer', Rule::exists(Teacher::class, 'id')->where(fn ($query) => $query->where('school_id', $schoolId)->where('status', Teacher::STATUS_ACTIVE)->whereNull('deleted_at'))],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->where(fn ($query) => $query->where('class_id', $this->input('class_id')))->ignore($subject->id)],
            'subject_type' => ['required', Rule::in([Subject::TYPE_THEORY, Subject::TYPE_PRACTICAL, Subject::TYPE_OPTIONAL])],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
