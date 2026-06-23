<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Attendance::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'attendance_date' => ['nullable', 'date_format:Y-m-d'],
            'class_id' => ['nullable', 'integer', 'min:1'],
            'section_id' => ['nullable', 'integer', 'min:1'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'marked_by' => ['prohibited'],
        ];
    }
}
