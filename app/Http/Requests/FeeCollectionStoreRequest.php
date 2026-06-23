<?php

namespace App\Http\Requests;

use App\Models\FeePayment;
use App\Models\StudentFee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeCollectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $studentFee = $this->route('student_fee');

        return $studentFee instanceof StudentFee
            && ($this->user()?->can('collect', $studentFee) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount_paid' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_mode' => ['required', 'string', Rule::in([
                FeePayment::MODE_CASH,
                FeePayment::MODE_CARD,
                FeePayment::MODE_UPI,
                FeePayment::MODE_BANK_TRANSFER,
                FeePayment::MODE_SANDBOX_GATEWAY,
            ])],
            'remarks' => ['nullable', 'string', 'max:500'],
            'collection_token' => ['required', 'uuid'],
            'school_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'student_fee_id' => ['prohibited'],
            'receipt_no' => ['prohibited'],
            'transaction_no' => ['prohibited'],
            'received_by' => ['prohibited'],
            'status' => ['prohibited'],
            'paid_amount' => ['prohibited'],
            'balance_amount' => ['prohibited'],
        ];
    }
}
