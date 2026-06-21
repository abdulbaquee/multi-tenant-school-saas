<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;

class SchoolClassRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolClass = $this->route('school_class');

        return $schoolClass instanceof SchoolClass
            && ($this->user()?->can('restore', $schoolClass) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
