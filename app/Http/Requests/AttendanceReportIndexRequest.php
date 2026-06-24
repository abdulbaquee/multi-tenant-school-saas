<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AttendanceReportIndexRequest extends ReportFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.attendance.view') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->sharedReportFilterRules(),
            'section_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in([
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_LEAVE,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HOLIDAY,
            ])],
        ];
    }

    protected function sharedReportFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'subject_id' => ['prohibited'],
            'exam_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'marked_by' => ['prohibited'],
            'received_by' => ['prohibited'],
            'entered_by' => ['prohibited'],
        ];
    }
}
