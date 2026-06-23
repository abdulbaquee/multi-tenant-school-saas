<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class ReportCardGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam
            && ($this->user()?->can('generate', $exam) ?? false);
    }

    public function rules(): array
    {
        return [
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'generated_by' => ['prohibited'],
        ];
    }
}
