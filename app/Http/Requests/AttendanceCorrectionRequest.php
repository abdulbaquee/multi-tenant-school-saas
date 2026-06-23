<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance instanceof Attendance
            && ($this->user()?->can('update', $attendance) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remarks' => filled($this->input('remarks'))
                ? trim((string) $this->input('remarks'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_LEAVE,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HOLIDAY,
            ])],
            'remarks' => ['nullable', 'string', 'max:500'],
            'school_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'section_id' => ['prohibited'],
            'attendance_date' => ['prohibited'],
            'marked_by' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
