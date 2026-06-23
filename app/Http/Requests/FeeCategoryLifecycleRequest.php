<?php

namespace App\Http\Requests;

use App\Models\FeeCategory;
use Illuminate\Foundation\Http\FormRequest;

class FeeCategoryLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feeCategory = $this->route('fee_category');

        if (! $feeCategory instanceof FeeCategory) {
            return false;
        }

        return match ($this->route()->getActionMethod()) {
            'activate' => $this->user()?->can('activate', $feeCategory) ?? false,
            'deactivate' => $this->user()?->can('deactivate', $feeCategory) ?? false,
            default => false,
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
