<?php

namespace App\Http\Requests;

use App\Models\PaymentTransaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SandboxTransactionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PaymentTransaction::class) ?? false;
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
            'status' => ['nullable', Rule::in([
                PaymentTransaction::STATUS_COMPLETED,
                PaymentTransaction::STATUS_PENDING,
                PaymentTransaction::STATUS_FAILED,
                PaymentTransaction::STATUS_REVERSED,
            ])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'school_id' => ['prohibited'],
            'fee_payment_id' => ['prohibited'],
            'payment_mode' => ['prohibited'],
        ];
    }
}
