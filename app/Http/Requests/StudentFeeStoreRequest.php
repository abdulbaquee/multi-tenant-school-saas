<?php

namespace App\Http\Requests;

use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentFeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StudentFee::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'fee_structure_id' => ['required', 'integer', Rule::exists('fee_structures', 'id')->where('school_id', $schoolId)->where('status', FeeStructure::STATUS_ACTIVE)->whereNull('deleted_at')],
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('school_id', $schoolId)->where('status', Student::STATUS_ACTIVE)->whereNull('deleted_at')],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'school_id' => ['prohibited'],
            'amount' => ['prohibited'],
            'payable_amount' => ['prohibited'],
            'paid_amount' => ['prohibited'],
            'balance_amount' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
