<?php

namespace App\Http\Requests\Concerns;

trait ProhibitsReportTenantOverride
{
    /**
     * @return array<string, list<string>>
     */
    protected function prohibitedReportScopeFields(): array
    {
        return [
            'school_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'class_id' => ['prohibited'],
            'section_id' => ['prohibited'],
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
