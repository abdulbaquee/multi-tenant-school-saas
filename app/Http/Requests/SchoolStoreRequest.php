<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', School::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => Str::upper(trim((string) $this->input('code'))),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'country' => trim((string) $this->input('country', 'India')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('schools', 'code')],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('schools', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'status' => ['prohibited'],
            'deactivated_at' => ['prohibited'],
            'deactivation_reason' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
