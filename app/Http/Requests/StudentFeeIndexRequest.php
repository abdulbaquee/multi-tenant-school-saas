<?php

namespace App\Http\Requests;

use App\Models\StudentFee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentFeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StudentFee::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', Rule::in([
                StudentFee::STATUS_PENDING,
                StudentFee::STATUS_PARTIAL,
                StudentFee::STATUS_PAID,
                StudentFee::STATUS_WAIVED,
                StudentFee::STATUS_CANCELLED,
            ])],
        ];
    }
}
