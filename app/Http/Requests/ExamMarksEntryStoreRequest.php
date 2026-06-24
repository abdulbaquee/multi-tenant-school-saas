<?php

namespace App\Http\Requests;

use App\Models\ExamResult;
use Illuminate\Foundation\Http\FormRequest;

class ExamMarksEntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('saveRoster', ExamResult::class) ?? false;
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

                if (array_key_exists('absent', $entry)) {
                    $entry['absent'] = filter_var($entry['absent'], FILTER_VALIDATE_BOOLEAN);
                }

                return $entry;
            })
            ->all();

        $this->merge(['entries' => $entries]);
    }

    public function rules(): array
    {
        return [
            'exam_subject_id' => ['required', 'integer', 'min:1'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.student_id' => ['required', 'integer', 'min:1', 'distinct:strict'],
            'entries.*.marks_obtained' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'entries.*.absent' => ['nullable', 'boolean'],
            'entries.*.remarks' => ['nullable', 'string', 'max:500'],
            'entries.*.school_id' => ['prohibited'],
            'entries.*.exam_id' => ['prohibited'],
            'entries.*.exam_subject_id' => ['prohibited'],
            'entries.*.subject_id' => ['prohibited'],
            'entries.*.grade_scale_id' => ['prohibited'],
            'entries.*.result_status' => ['prohibited'],
            'entries.*.entered_by' => ['prohibited'],
            'entries.*.created_at' => ['prohibited'],
            'entries.*.updated_at' => ['prohibited'],
            'school_id' => ['prohibited'],
            'exam_id' => ['prohibited'],
            'subject_id' => ['prohibited'],
            'grade_scale_id' => ['prohibited'],
            'result_status' => ['prohibited'],
            'entered_by' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
