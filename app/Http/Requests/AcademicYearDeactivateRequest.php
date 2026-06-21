<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;

class AcademicYearDeactivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear instanceof AcademicYear
            && ($this->user()?->can('deactivate', $academicYear) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
