<?php

namespace App\Http\Requests;

use App\Models\StudentFee;
use Illuminate\Contracts\Validation\ValidationRule;

class FeeReportIndexRequest extends ReportFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.fees.view') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedReportFilterRules(),
            'state' => ['nullable', 'string', 'in:'.implode(',', [
                StudentFee::STATUS_PENDING,
                StudentFee::STATUS_PARTIAL,
                StudentFee::STATUS_PAID,
                StudentFee::STATUS_WAIVED,
            ])],
        ];
    }
}
