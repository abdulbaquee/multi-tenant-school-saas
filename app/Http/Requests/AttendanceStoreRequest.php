<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('saveRoster', Attendance::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $entries = collect($this->input('entries', []))
            ->map(function (mixed $entry): mixed {
                if (! is_array($entry)) {
                    return $entry;
                }

                $entry['remarks'] = filled($entry['remarks'] ?? null)
                    ? trim((string) $entry['remarks'])
                    : null;

                return $entry;
            })
            ->all();

        $this->merge(['entries' => $entries]);
    }

    public function rules(): array
    {
        return [
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'section_id' => ['required', 'integer', 'min:1'],
            'mode' => ['required', Rule::in(['roster', 'holiday'])],
            'entries' => ['exclude_if:mode,holiday', 'required_if:mode,roster', 'array', 'min:1'],
            'entries.*.student_id' => ['required_if:mode,roster', 'integer', 'min:1', 'distinct:strict'],
            'entries.*.status' => [
                'required_if:mode,roster',
                Rule::in([
                    Attendance::STATUS_PRESENT,
                    Attendance::STATUS_ABSENT,
                    Attendance::STATUS_LEAVE,
                    Attendance::STATUS_LATE,
                ]),
            ],
            'entries.*.remarks' => ['nullable', 'string', 'max:500'],
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'student_id' => ['prohibited'],
            'marked_by' => ['prohibited'],
            'status' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
