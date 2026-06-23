<?php

namespace App\Http\Requests;

use App\Models\ExamResult;
use Illuminate\Foundation\Http\FormRequest;

class ExamMarksEntryWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ExamResult::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'exam_subject_id' => ['nullable', 'integer', 'min:1'],
            'school_id' => ['prohibited'],
            'exam_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'entered_by' => ['prohibited'],
        ];
    }
}
