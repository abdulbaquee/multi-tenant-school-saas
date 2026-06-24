<?php

namespace App\Http\Requests;

use App\Models\ExamResult;
use Illuminate\Contracts\Validation\ValidationRule;

class ExaminationReportIndexRequest extends ReportFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.examinations.view') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedReportFilterRules(),
            'status' => ['nullable', 'string', 'in:'.implode(',', [
                ExamResult::STATUS_PENDING,
                ExamResult::STATUS_PASS,
                ExamResult::STATUS_FAIL,
                ExamResult::STATUS_ABSENT,
            ])],
        ];
    }
}
