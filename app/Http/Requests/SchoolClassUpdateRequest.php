<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolClassUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolClass = $this->route('school_class');

        return $schoolClass instanceof SchoolClass
            && ($this->user()?->can('update', $schoolClass) ?? false);
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
        /** @var SchoolClass $schoolClass */
        $schoolClass = $this->route('school_class');

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('classes', 'name')->where('school_id', $this->user()?->school_id)->ignore($schoolClass->id)],
            'code' => ['required', 'string', 'max:30', Rule::unique('classes', 'code')->where('school_id', $this->user()?->school_id)->ignore($schoolClass->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
