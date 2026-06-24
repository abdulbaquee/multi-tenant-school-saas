<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ReportIndexRequest extends ReportFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->sharedReportFilterRules();
    }
}
