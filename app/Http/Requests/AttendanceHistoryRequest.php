<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Attendance::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => filled($this->input('search')) ? trim((string) $this->input('search')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'section_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in([
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_LEAVE,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HOLIDAY,
            ])],
            'search' => ['nullable', 'string', 'max:150'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'marked_by' => ['prohibited'],
        ];
    }
}
