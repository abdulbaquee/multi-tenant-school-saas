<?php

namespace App\Http\Requests;

use App\Models\FeeStructure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeStructureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feeStructure = $this->route('fee_structure');

        return $feeStructure instanceof FeeStructure
            && ($this->user()?->can('update', $feeStructure) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'due_date' => ['nullable', 'date'],
            'frequency' => ['required', 'string', Rule::in([
                FeeStructure::FREQUENCY_ONE_TIME,
                FeeStructure::FREQUENCY_MONTHLY,
                FeeStructure::FREQUENCY_QUARTERLY,
                FeeStructure::FREQUENCY_ANNUAL,
            ])],
            'fee_category_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
