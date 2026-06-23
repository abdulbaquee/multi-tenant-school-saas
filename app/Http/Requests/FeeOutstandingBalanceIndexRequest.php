<?php

namespace App\Http\Requests;

use App\Models\StudentFee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeOutstandingBalanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StudentFee::class) ?? false;
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
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', Rule::in([StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])],
            'school_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'fee_structure_id' => ['prohibited'],
        ];
    }
}
