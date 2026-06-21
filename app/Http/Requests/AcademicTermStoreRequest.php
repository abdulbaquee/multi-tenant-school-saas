<?php

namespace App\Http\Requests;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicTermStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicTerm::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicYearId = $this->integer('academic_year_id');

        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')
                    ->where('school_id', $this->user()?->school_id)
                    ->where('status', AcademicYear::STATUS_ACTIVE),
            ],
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('academic_terms', 'name')->where('academic_year_id', $academicYearId),
            ],
            'term_order' => [
                'required',
                'integer',
                'min:1',
                'max:255',
                Rule::unique('academic_terms', 'term_order')->where('academic_year_id', $academicYearId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
