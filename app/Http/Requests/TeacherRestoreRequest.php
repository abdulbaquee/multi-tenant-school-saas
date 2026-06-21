<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;

class TeacherRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher_profile');

        return $teacher instanceof Teacher
            && ($this->user()?->can('restore', $teacher) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
