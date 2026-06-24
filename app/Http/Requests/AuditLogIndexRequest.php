<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class AuditLogIndexRequest extends LogReviewIndexRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'event' => filled($this->input('event')) ? trim((string) $this->input('event')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedLogFilterRules(),
            'event' => ['nullable', 'string', 'max:80'],
        ];
    }
}
