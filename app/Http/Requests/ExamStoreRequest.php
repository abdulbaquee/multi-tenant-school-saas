<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Exam::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'academic_term_id' => filled($this->input('academic_term_id')) ? (int) $this->input('academic_term_id') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->where('status', 'active')
                    ->where('is_current', true)),
            ],
            'academic_term_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_terms', 'id')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->where('status', 'active')),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('exams', 'name')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->whereNull('deleted_at')),
            ],
            'exam_type' => ['required', 'string', Rule::in([
                Exam::TYPE_TERM,
                Exam::TYPE_UNIT_TEST,
                Exam::TYPE_FINAL,
                Exam::TYPE_OTHER,
            ])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
