<?php

namespace App\Http\Requests;

use App\Models\FeeCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feeCategory = $this->route('fee_category');

        return $feeCategory instanceof FeeCategory
            && ($this->user()?->can('update', $feeCategory) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => filled($this->input('description')) ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var FeeCategory $feeCategory */
        $feeCategory = $this->route('fee_category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('fee_categories', 'name')->where('school_id', $this->user()?->school_id)->ignore($feeCategory->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
