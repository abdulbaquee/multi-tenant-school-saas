<?php

namespace App\Http\Requests;

use App\Models\FeeStructure;
use Illuminate\Foundation\Http\FormRequest;

class FeeStructureLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feeStructure = $this->route('fee_structure');

        if (! $feeStructure instanceof FeeStructure) {
            return false;
        }

        return match ($this->route()->getActionMethod()) {
            'activate' => $this->user()?->can('activate', $feeStructure) ?? false,
            'deactivate' => $this->user()?->can('deactivate', $feeStructure) ?? false,
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
