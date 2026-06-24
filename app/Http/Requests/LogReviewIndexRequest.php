<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProhibitsReportTenantOverride;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class LogReviewIndexRequest extends FormRequest
{
    use ProhibitsReportTenantOverride;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search')) ? trim((string) $this->input('search')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function sharedLogFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            ...$this->prohibitedReportScopeFields(),
        ];
    }
}
