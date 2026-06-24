<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ActivityLogIndexRequest extends LogReviewIndexRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'module' => filled($this->input('module')) ? trim((string) $this->input('module')) : null,
            'action' => filled($this->input('action')) ? trim((string) $this->input('action')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedLogFilterRules(),
            'module' => ['nullable', 'string', 'max:80'],
            'action' => ['nullable', 'string', 'max:80'],
        ];
    }
}
