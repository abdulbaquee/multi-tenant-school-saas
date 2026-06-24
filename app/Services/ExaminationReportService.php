<?php

namespace App\Services;

use App\Models\ExamResult;
use App\Models\Role;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ExaminationReportService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReportService $reports,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function reportFor(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->authorizeCategory($actor);

        $filters = $this->normalizeFilters($filters);

        if ($actor->isSuperAdmin()) {
            return $this->platformReport($actor, $filters);
        }

        return $this->schoolReport($actor, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<list<string|int>>
     */
    public function exportRowsFor(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->authorizeCategory($actor);
        $filters = $this->normalizeFilters($filters);

        if ($actor->isSuperAdmin()) {
            $schools = School::query()
                ->withCount([
                    'examResults as total_results_count' => function (Builder $builder) use ($filters): void {
                        $this->applyExaminationFilters($builder, $filters);
                    },
                    'examResults as pass_results_count' => function (Builder $builder) use ($filters): void {
                        $this->applyExaminationFilters($builder, $filters);
                        $builder->where('result_status', ExamResult::STATUS_PASS);
                    },
                    'examResults as fail_results_count' => function (Builder $builder) use ($filters): void {
                        $this->applyExaminationFilters($builder, $filters);
                        $builder->where('result_status', ExamResult::STATUS_FAIL);
                    },
                    'examResults as absent_results_count' => function (Builder $builder) use ($filters): void {
                        $this->applyExaminationFilters($builder, $filters);
                        $builder->where('result_status', ExamResult::STATUS_ABSENT);
                    },
                ])
                ->when(filled($filters['search'] ?? null), function (Builder $builder) use ($filters): void {
                    $search = '%'.$filters['search'].'%';
                    $builder->where(fn (Builder $nested) => $nested
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search));
                })
                ->orderBy('name')
                ->get();

            return $schools->map(fn (School $school): array => [
                $school->code,
                $school->name,
                (string) ($school->total_results_count ?? 0),
                (string) ($school->pass_results_count ?? 0),
                (string) ($school->fail_results_count ?? 0),
                (string) ($school->absent_results_count ?? 0),
            ])->all();
        }

        $baseQuery = $this->scopedResultsQuery($actor);
        $this->applyExaminationFilters($baseQuery, $filters);

        $results = (clone $baseQuery)
            ->with([
                'exam:id,name,start_date',
                'subject:id,name',
                'student:id,admission_no,first_name,last_name',
                'gradeScale:id,grade',
            ])
            ->orderByDesc('id')
            ->get();

        return $results->map(fn (ExamResult $result): array => [
            $result->exam->name,
            $result->subject->name,
            $result->student->admission_no,
            trim($result->student->first_name.' '.$result->student->last_name),
            $result->marks_obtained !== null ? number_format((float) $result->marks_obtained, 2, '.', '') : '—',
            $result->gradeScale?->grade ?? '—',
            ucfirst((string) $result->result_status),
        ])->all();
    }

    /**
     * @return list<string>
     */
    public function exportHeadersFor(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? ['School Code', 'School Name', 'Results', 'Pass', 'Fail', 'Absent']
            : ['Exam', 'Subject', 'Admission No', 'Student Name', 'Marks', 'Grade', 'Status'];
    }

    public function logExport(User $actor, int $rowCount): void
    {
        $this->securityLogs->activity(
            $actor,
            'reporting',
            'exported',
            null,
            'Examination report exported with '.$rowCount.' row(s).',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function platformReport(User $actor, array $filters): array
    {
        $query = School::query()
            ->withCount([
                'examResults as total_results_count' => function (Builder $builder) use ($filters): void {
                    $this->applyExaminationFilters($builder, $filters);
                },
                'examResults as pass_results_count' => function (Builder $builder) use ($filters): void {
                    $this->applyExaminationFilters($builder, $filters);
                    $builder->where('result_status', ExamResult::STATUS_PASS);
                },
                'examResults as fail_results_count' => function (Builder $builder) use ($filters): void {
                    $this->applyExaminationFilters($builder, $filters);
                    $builder->where('result_status', ExamResult::STATUS_FAIL);
                },
                'examResults as absent_results_count' => function (Builder $builder) use ($filters): void {
                    $this->applyExaminationFilters($builder, $filters);
                    $builder->where('result_status', ExamResult::STATUS_ABSENT);
                },
            ])
            ->when(filled($filters['search'] ?? null), function (Builder $builder) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $builder->where(fn (Builder $nested) => $nested
                    ->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search));
            })
            ->orderBy('name');

        $schools = $query->paginate(15)->withQueryString();
        $totals = ExamResult::query();
        $this->applyExaminationFilters($totals, $filters);

        return [
            'mode' => 'platform',
            'title' => 'Examination Reports',
            'description' => 'Platform-wide examination result summaries by school.',
            'summary' => [
                [
                    'label' => 'Schools listed',
                    'value' => $schools->total(),
                    'icon' => 'bi-buildings',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Result records',
                    'value' => (clone $totals)->count(),
                    'icon' => 'bi-journal-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Pass records',
                    'value' => (clone $totals)->where('result_status', ExamResult::STATUS_PASS)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'secondary',
                ],
            ],
            'schools' => $schools,
            'canExport' => Gate::forUser($actor)->allows('reports.examinations.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function schoolReport(User $actor, array $filters): array
    {
        $baseQuery = $this->scopedResultsQuery($actor);
        $this->applyExaminationFilters($baseQuery, $filters);

        $summaryQuery = clone $baseQuery;

        $results = (clone $baseQuery)
            ->with([
                'exam:id,name,start_date',
                'subject:id,name',
                'student:id,admission_no,first_name,last_name',
                'gradeScale:id,grade',
            ])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return [
            'mode' => 'school',
            'title' => 'Examination Reports',
            'description' => $actor->hasRoleCode(Role::TEACHER)
                ? 'Examination result summaries for your assigned subjects only.'
                : 'Examination result summaries for your school.',
            'summary' => [
                [
                    'label' => 'Matching results',
                    'value' => $results->total(),
                    'icon' => 'bi-journal-check',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Pass',
                    'value' => (clone $summaryQuery)->where('result_status', ExamResult::STATUS_PASS)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Fail',
                    'value' => (clone $summaryQuery)->where('result_status', ExamResult::STATUS_FAIL)->count(),
                    'icon' => 'bi-x-circle',
                    'tone' => 'secondary',
                ],
            ],
            'results' => $results,
            'canExport' => Gate::forUser($actor)->allows('reports.examinations.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{search: ?string, date_from: ?string, date_to: ?string, status: ?string}
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = $this->reports->normalizeFilters($filters);
        $status = filled($filters['status'] ?? null) ? (string) $filters['status'] : null;

        if ($status !== null && ! in_array($status, [
            ExamResult::STATUS_PENDING,
            ExamResult::STATUS_PASS,
            ExamResult::STATUS_FAIL,
            ExamResult::STATUS_ABSENT,
        ], true)) {
            $status = null;
        }

        return [
            ...$normalized,
            'status' => $status,
        ];
    }

    /**
     * @param  Builder<ExamResult>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyExaminationFilters(Builder $query, array $filters): void
    {
        $query
            ->when(filled($filters['status'] ?? null), fn (Builder $builder) => $builder->where('result_status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $builder) => $builder->whereHas('exam', fn (Builder $examQuery) => $examQuery->whereDate('start_date', '>=', $filters['date_from'])))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $builder) => $builder->whereHas('exam', fn (Builder $examQuery) => $examQuery->whereDate('start_date', '<=', $filters['date_to'])));

        if (! filled($filters['search'] ?? null)) {
            return;
        }

        $search = '%'.$filters['search'].'%';
        $query->where(function (Builder $nested) use ($search): void {
            $nested
                ->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                    ->where('admission_no', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search))
                ->orWhereHas('exam', fn (Builder $examQuery) => $examQuery->where('name', 'like', $search))
                ->orWhereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('name', 'like', $search));
        });
    }

    /**
     * @return Builder<ExamResult>
     */
    private function scopedResultsQuery(User $actor): Builder
    {
        $query = ExamResult::query();

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);

            if (! $teacher instanceof Teacher) {
                throw new AuthorizationException;
            }

            $query->whereHas('examSubject.subject', fn (Builder $subjectQuery) => $subjectQuery->where('teacher_id', $teacher->id));
        }

        return $query;
    }

    private function activeTeacherProfile(User $actor): ?Teacher
    {
        if (! $actor->hasRoleCode(Role::TEACHER)) {
            return null;
        }

        return $actor->teacherProfile()
            ->whereNull('deleted_at')
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
    }

    private function authorizeCategory(User $actor): void
    {
        if (! Gate::forUser($actor)->allows('reports.examinations.view')) {
            throw new AuthorizationException;
        }
    }

    private function authorizeActorContext(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $this->tenantContext->isTenant()
                && filled($actor->school_id)
                && (int) $this->tenantContext->schoolId() === (int) $actor->school_id
                && ($actor->hasRoleCode(Role::SCHOOL_ADMIN) || $actor->hasRoleCode(Role::TEACHER));

        if (! $matchesContext) {
            throw new AuthorizationException;
        }
    }
}
