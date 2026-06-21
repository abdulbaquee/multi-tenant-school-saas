<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class StudentLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');
        $ability = match ($this->route()?->getName()) {
            'students.activate' => 'activate',
            'students.deactivate' => 'deactivate',
            'students.archive' => 'archive',
            'students.restore' => 'restore',
            default => null,
        };

        return $student instanceof Student
            && filled($ability)
            && ($this->user()?->can($ability, $student) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
