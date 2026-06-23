<?php

namespace App\Http\Requests;

use App\Models\ReportCard;
use Illuminate\Foundation\Http\FormRequest;

class ReportCardIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportCard::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'exam_id' => ['nullable', 'integer', 'min:1'],
            'school_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'generated_by' => ['prohibited'],
        ];
    }
}
