<?php

namespace App\Http\Requests;

use App\Models\AcademicTerm;
use Illuminate\Foundation\Http\FormRequest;

class AcademicTermDeactivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicTerm = $this->route('academic_term');

        return $academicTerm instanceof AcademicTerm
            && ($this->user()?->can('deactivate', $academicTerm) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
