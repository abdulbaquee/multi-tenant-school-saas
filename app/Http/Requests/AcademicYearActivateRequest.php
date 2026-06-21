<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;

class AcademicYearActivateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear instanceof AcademicYear
            && ($this->user()?->can('activate', $academicYear) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
