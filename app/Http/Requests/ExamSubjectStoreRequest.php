<?php

namespace App\Http\Requests;

use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamSubjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExamSubject::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => [
                'required',
                'integer',
                Rule::exists('exams', 'id')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->whereIn('status', [Exam::STATUS_SCHEDULED, Exam::STATUS_ONGOING])
                    ->whereNull('deleted_at')),
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query
                    ->where('school_id', $this->user()?->school_id)
                    ->where('class_id', $this->input('class_id'))
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'exam_date' => ['nullable', 'date'],
            'max_marks' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'passing_marks' => ['required', 'numeric', 'min:0', 'max:9999.99', 'lte:max_marks'],
            'school_id' => ['prohibited'],
        ];
    }
}
