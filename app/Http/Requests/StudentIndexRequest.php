<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Student::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'state' => [
                'nullable',
                Rule::in([
                    Student::STATUS_ACTIVE,
                    Student::STATUS_INACTIVE,
                    Student::STATUS_TRANSFERRED,
                    Student::STATUS_GRADUATED,
                    'archived',
                ]),
            ],
            'school_id' => $this->user()?->isSuperAdmin()
                ? ['nullable', 'integer', Rule::exists('schools', 'id')->whereNull('deleted_at')]
                : ['prohibited'],
        ];
    }
}
