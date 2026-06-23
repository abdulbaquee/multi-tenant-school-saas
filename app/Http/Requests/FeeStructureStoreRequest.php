<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeStructureStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FeeStructure::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'fee_category_id' => ['required', 'integer', Rule::exists('fee_categories', 'id')->where('school_id', $schoolId)->where('status', FeeCategory::STATUS_ACTIVE)->whereNull('deleted_at')],
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)->where('status', AcademicYear::STATUS_ACTIVE)->where('is_current', true)],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'due_date' => ['nullable', 'date'],
            'frequency' => ['required', 'string', Rule::in([
                FeeStructure::FREQUENCY_ONE_TIME,
                FeeStructure::FREQUENCY_MONTHLY,
                FeeStructure::FREQUENCY_QUARTERLY,
                FeeStructure::FREQUENCY_ANNUAL,
            ])],
            'school_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
