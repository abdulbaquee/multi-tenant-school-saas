<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class SectionLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');
        $ability = match ($this->route()?->getName()) {
            'sections.activate' => 'activate',
            'sections.deactivate' => 'deactivate',
            'sections.archive' => 'archive',
            'sections.restore' => 'restore',
            default => null,
        };

        return $section instanceof Section
            && filled($ability)
            && ($this->user()?->can($ability, $section) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
