<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;

class AttendanceReportService
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
                    'attendances as total_records' => function (Builder $builder) use ($filters): void {
                        $this->applyAttendanceFilters($builder, $filters);
                    },
                    'attendances as present_records' => function (Builder $builder) use ($filters): void {
                        $this->applyAttendanceFilters($builder, $filters);
                        $builder->where('status', Attendance::STATUS_PRESENT);
                    },
                    'attendances as absent_records' => function (Builder $builder) use ($filters): void {
                        $this->applyAttendanceFilters($builder, $filters);
                        $builder->where('status', Attendance::STATUS_ABSENT);
                    },
                    'attendances as leave_records' => function (Builder $builder) use ($filters): void {
                        $this->applyAttendanceFilters($builder, $filters);
                        $builder->where('status', Attendance::STATUS_LEAVE);
                    },
                    'attendances as late_records' => function (Builder $builder) use ($filters): void {
                        $this->applyAttendanceFilters($builder, $filters);
                        $builder->where('status', Attendance::STATUS_LATE);
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
                (string) ($school->total_records ?? 0),
                (string) ($school->present_records ?? 0),
                (string) ($school->absent_records ?? 0),
                (string) ($school->leave_records ?? 0),
                (string) ($school->late_records ?? 0),
            ])->all();
        }

        $authorizedSectionIds = $this->authorizedSectionIds($actor);
        $baseQuery = $this->scopedAttendanceQuery($actor, $authorizedSectionIds);
        $this->applyAttendanceFilters($baseQuery, $filters, $authorizedSectionIds);

        $attendances = (clone $baseQuery)
            ->with(['student', 'schoolClass', 'section.schoolClass'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        return $attendances->map(fn (Attendance $attendance): array => [
            $attendance->attendance_date->format('Y-m-d'),
            $attendance->student->admission_no,
            trim($attendance->student->first_name.' '.$attendance->student->last_name),
            $attendance->schoolClass->name.' / '.$attendance->section->name,
            ucfirst($attendance->status),
        ])->all();
    }

    /**
     * @return list<string>
     */
    public function exportHeadersFor(User $actor): array
    {
        return $actor->isSuperAdmin()
          ? ['School Code', 'School Name', 'Records', 'Present', 'Absent', 'Leave', 'Late']
          : ['Date', 'Admission No', 'Student Name', 'Placement', 'Status'];
    }

    public function logExport(User $actor, int $rowCount): void
    {
        $this->securityLogs->activity(
            $actor,
            'reporting',
            'exported',
            null,
            'Attendance report exported with '.$rowCount.' row(s).',
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
                'attendances as total_records' => function (Builder $builder) use ($filters): void {
                    $this->applyAttendanceFilters($builder, $filters);
                },
                'attendances as present_records' => function (Builder $builder) use ($filters): void {
                    $this->applyAttendanceFilters($builder, $filters);
                    $builder->where('status', Attendance::STATUS_PRESENT);
                },
                'attendances as absent_records' => function (Builder $builder) use ($filters): void {
                    $this->applyAttendanceFilters($builder, $filters);
                    $builder->where('status', Attendance::STATUS_ABSENT);
                },
                'attendances as leave_records' => function (Builder $builder) use ($filters): void {
                    $this->applyAttendanceFilters($builder, $filters);
                    $builder->where('status', Attendance::STATUS_LEAVE);
                },
                'attendances as late_records' => function (Builder $builder) use ($filters): void {
                    $this->applyAttendanceFilters($builder, $filters);
                    $builder->where('status', Attendance::STATUS_LATE);
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
        $totals = Attendance::query();
        $this->applyAttendanceFilters($totals, $filters);

        return [
            'mode' => 'platform',
            'title' => 'Attendance Reports',
            'description' => 'Platform-wide attendance summaries by school.',
            'summary' => [
                [
                    'label' => 'Schools listed',
                    'value' => $schools->total(),
                    'icon' => 'bi-buildings',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Attendance records',
                    'value' => (clone $totals)->count(),
                    'icon' => 'bi-calendar2-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Present records',
                    'value' => (clone $totals)->where('status', Attendance::STATUS_PRESENT)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'secondary',
                ],
            ],
            'schools' => $schools,
            'sections' => collect(),
            'canExport' => Gate::forUser($actor)->allows('reports.attendance.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function schoolReport(User $actor, array $filters): array
    {
        $authorizedSectionIds = $this->authorizedSectionIds($actor);
        $baseQuery = $this->scopedAttendanceQuery($actor, $authorizedSectionIds);
        $this->applyAttendanceFilters($baseQuery, $filters, $authorizedSectionIds);

        $summaryQuery = clone $baseQuery;

        $attendances = (clone $baseQuery)
            ->with(['student', 'schoolClass', 'section.schoolClass'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return [
            'mode' => 'school',
            'title' => 'Attendance Reports',
            'description' => 'Attendance totals and retained records within your authorized scope.',
            'summary' => [
                [
                    'label' => 'Matching records',
                    'value' => $attendances->total(),
                    'icon' => 'bi-calendar2-check',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Present',
                    'value' => (clone $summaryQuery)->where('status', Attendance::STATUS_PRESENT)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Absent',
                    'value' => (clone $summaryQuery)->where('status', Attendance::STATUS_ABSENT)->count(),
                    'icon' => 'bi-x-circle',
                    'tone' => 'secondary',
                ],
            ],
            'attendances' => $attendances,
            'sections' => $this->sectionOptions($actor),
            'canExport' => Gate::forUser($actor)->allows('reports.attendance.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{search: ?string, date_from: ?string, date_to: ?string, status: ?string, section_id: ?int}
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = $this->reports->normalizeFilters($filters);
        $sectionId = filter_var($filters['section_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return [
            ...$normalized,
            'status' => filled($filters['status'] ?? null) ? (string) $filters['status'] : null,
            'section_id' => $sectionId === false ? null : $sectionId,
        ];
    }

    /**
     * @param  Builder<Attendance>  $query
     * @param  array<string, mixed>  $filters
     * @param  SupportCollection<int, int>|null  $authorizedSectionIds
     */
    private function applyAttendanceFilters(
        Builder $query,
        array $filters,
        ?SupportCollection $authorizedSectionIds = null,
    ): void {
        if ($authorizedSectionIds instanceof SupportCollection && $authorizedSectionIds->isNotEmpty()) {
            $query->whereIn('section_id', $authorizedSectionIds);
        }

        if (filled($filters['section_id'] ?? null)) {
            $sectionId = (int) $filters['section_id'];

            if ($authorizedSectionIds instanceof SupportCollection && ! $authorizedSectionIds->contains($sectionId)) {
                throw (new ModelNotFoundException)->setModel(Section::class, [$sectionId]);
            }

            $query->where('section_id', $sectionId);
        }

        $query
            ->when(filled($filters['date_from'] ?? null), fn (Builder $builder) => $builder->whereDate('attendance_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $builder) => $builder->whereDate('attendance_date', '<=', $filters['date_to']))
            ->when(filled($filters['status'] ?? null), fn (Builder $builder) => $builder->where('status', $filters['status']));

        foreach (preg_split('/\s+/', trim((string) ($filters['search'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $term) {
            $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('admission_no', 'like', "%{$term}%"));
        }
    }

    /**
     * @param  SupportCollection<int, int>  $authorizedSectionIds
     * @return Builder<Attendance>
     */
    private function scopedAttendanceQuery(User $actor, SupportCollection $authorizedSectionIds): Builder
    {
        $query = Attendance::query();

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $query->whereIn('section_id', $authorizedSectionIds);
        }

        return $query;
    }

    /**
     * @return SupportCollection<int, int>
     */
    private function authorizedSectionIds(User $actor): SupportCollection
    {
        return $this->sectionOptions($actor)->pluck('id')->map(fn (mixed $id): int => (int) $id);
    }

    /**
     * @return Collection<int, Section>
     */
    private function sectionOptions(User $actor): Collection
    {
        $query = Section::query()
            ->with('schoolClass')
            ->orderBy('class_id')
            ->orderBy('name');

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);

            return $query
                ->whereNull('deleted_at')
                ->where('status', Section::STATUS_ACTIVE)
                ->where('teacher_id', $teacher->id)
                ->get();
        }

        return $query->withTrashed()->get();
    }

    private function authorizeCategory(User $actor): void
    {
        if (! Gate::forUser($actor)->allows('reports.attendance.view')) {
            throw new AuthorizationException;
        }
    }

    private function authorizeActorContext(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
          ? $this->tenantContext->isPlatform()
          : $this->tenantContext->isTenant()
            && filled($actor->school_id)
            && (int) $this->tenantContext->schoolId() === (int) $actor->school_id;

        if (! $matchesContext) {
            throw new AuthorizationException;
        }
    }

    private function activeTeacherProfile(User $actor): Teacher
    {
        $teacher = $actor->teacherProfile()
            ->whereNull('deleted_at')
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();

        if (! $teacher instanceof Teacher) {
            throw new AuthorizationException;
        }

        return $teacher;
    }
}
