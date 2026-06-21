<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolClassIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SchoolClass::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
