<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;

class SchoolClassDeactivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolClass = $this->route('school_class');

        return $schoolClass instanceof SchoolClass
            && ($this->user()?->can('deactivate', $schoolClass) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
