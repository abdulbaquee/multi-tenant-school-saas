<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && ($this->user()?->can('view', $student) ?? false);
    }

    public function rules(): array
    {
        return [
            'school_id' => $this->user()?->isSuperAdmin()
                ? ['required', 'integer', Rule::exists('schools', 'id')->whereNull('deleted_at')]
                : ['prohibited'],
        ];
    }
}
