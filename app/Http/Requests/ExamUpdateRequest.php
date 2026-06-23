<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && ($this->user()?->can('update', $exam) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_type' => ['required', 'string', Rule::in([
                Exam::TYPE_TERM,
                Exam::TYPE_UNIT_TEST,
                Exam::TYPE_FINAL,
                Exam::TYPE_OTHER,
            ])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_year_id' => ['prohibited'],
            'academic_term_id' => ['prohibited'],
            'name' => ['prohibited'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
