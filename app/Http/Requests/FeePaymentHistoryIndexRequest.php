<?php

namespace App\Http\Requests;

use App\Models\FeePayment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeePaymentHistoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FeePayment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search')) ? trim((string) $this->input('search')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'payment_mode' => ['nullable', 'string', Rule::in([
                FeePayment::MODE_CASH,
                FeePayment::MODE_CARD,
                FeePayment::MODE_UPI,
                FeePayment::MODE_BANK_TRANSFER,
                FeePayment::MODE_SANDBOX_GATEWAY,
            ])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'school_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'received_by' => ['prohibited'],
        ];
    }
}
