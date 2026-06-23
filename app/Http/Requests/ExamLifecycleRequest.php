<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class ExamLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        if (! $exam instanceof Exam) {
            return false;
        }

        return match ($this->route()->getActionMethod()) {
            'publish' => $this->user()?->can('publish', $exam) ?? false,
            'complete' => $this->user()?->can('complete', $exam) ?? false,
            'cancel' => $this->user()?->can('cancel', $exam) ?? false,
            'archive' => $this->user()?->can('archive', $exam) ?? false,
            default => false,
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
