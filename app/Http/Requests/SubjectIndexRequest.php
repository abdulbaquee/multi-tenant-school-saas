<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Subject::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
            'class_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', Rule::in([Subject::TYPE_THEORY, Subject::TYPE_PRACTICAL, Subject::TYPE_OPTIONAL])],
        ];
    }
}
