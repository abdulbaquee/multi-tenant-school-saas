<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceMonthlySummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Attendance::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', 'min:1'],
            'month' => ['nullable', 'date_format:Y-m'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'marked_by' => ['prohibited'],
        ];
    }
}
