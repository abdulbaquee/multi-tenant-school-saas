<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;

class SubjectLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');
        $ability = match ($this->route()?->getName()) {
            'subjects.activate' => 'activate',
            'subjects.deactivate' => 'deactivate',
            'subjects.archive' => 'archive',
            'subjects.restore' => 'restore',
            default => null,
        };

        return $subject instanceof Subject
            && filled($ability)
            && ($this->user()?->can($ability, $subject) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
