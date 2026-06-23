<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class ExamResultProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam
            && ($this->user()?->can('process', $exam) ?? false);
    }

    public function rules(): array
    {
        return [
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
