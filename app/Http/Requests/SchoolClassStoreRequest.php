<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolClassStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SchoolClass::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => Str::upper(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('classes', 'name')->where('school_id', $this->user()?->school_id)],
            'code' => ['required', 'string', 'max:30', Rule::unique('classes', 'code')->where('school_id', $this->user()?->school_id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
