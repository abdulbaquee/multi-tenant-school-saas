<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;

class StudentReportService
{
    /**
     * @var list<string>
     */
    private const PRIVACY_COLUMNS = [
        'id',
        'school_id',
        'admission_no',
        'first_name',
        'last_name',
        'status',
        'deleted_at',
    ];

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
                    'students as active_students_count' => fn (Builder $builder) => $builder
                        ->whereNull('deleted_at')
                        ->where('status', Student::STATUS_ACTIVE),
                    'students as enrolled_students_count' => fn (Builder $builder) => $builder
                        ->whereNull('deleted_at')
                        ->where('status', Student::STATUS_ACTIVE)
                        ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                            ->where('status', StudentEnrollment::STATUS_ACTIVE)),
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
                (string) ($school->active_students_count ?? 0),
                (string) ($school->enrolled_students_count ?? 0),
            ])->all();
        }

        $authorizedSectionIds = $this->authorizedSectionIds($actor);
        $query = $this->scopedStudentQuery($actor);
        $this->applyStudentFilters($query, $actor, $filters, $authorizedSectionIds);

        $students = $query
            ->with([
                'enrollments' => function (HasMany $enrollmentQuery) use ($actor): void {
                    $enrollmentQuery
                        ->where('status', StudentEnrollment::STATUS_ACTIVE)
                        ->with(['schoolClass', 'section'])
                        ->latest('enrollment_date');

                    if ($this->isTeacher($actor)) {
                        $teacher = $this->activeTeacherProfile($actor);
                        $this->applyTeacherEnrollmentScope($enrollmentQuery, $teacher);
                    }
                },
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('admission_no')
            ->get();

        return $students->map(function (Student $student): array {
            $enrollment = $student->enrollments->first();

            return [
                $student->admission_no,
                trim($student->first_name.' '.$student->last_name),
                $enrollment?->schoolClass?->name ?? '—',
                $enrollment?->section?->name ?? '—',
                ucfirst((string) $student->status),
            ];
        })->all();
    }

    /**
     * @return list<string>
     */
    public function exportHeadersFor(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? ['School Code', 'School Name', 'Active Students', 'Enrolled Students']
            : ['Admission No', 'Student Name', 'Class', 'Section', 'Status'];
    }

    public function logExport(User $actor, int $rowCount): void
    {
        $this->securityLogs->activity(
            $actor,
            'reporting',
            'exported',
            null,
            'Student report exported with '.$rowCount.' row(s).',
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
                'students as active_students_count' => fn (Builder $builder) => $builder
                    ->whereNull('deleted_at')
                    ->where('status', Student::STATUS_ACTIVE),
                'students as enrolled_students_count' => fn (Builder $builder) => $builder
                    ->whereNull('deleted_at')
                    ->where('status', Student::STATUS_ACTIVE)
                    ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                        ->where('status', StudentEnrollment::STATUS_ACTIVE)),
            ])
            ->when(filled($filters['search'] ?? null), function (Builder $builder) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $builder->where(fn (Builder $nested) => $nested
                    ->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search));
            })
            ->orderBy('name');

        $schools = $query->paginate(15)->withQueryString();

        return [
            'mode' => 'platform',
            'title' => 'Student Reports',
            'description' => 'Platform-wide student enrollment summaries by school.',
            'summary' => [
                [
                    'label' => 'Schools listed',
                    'value' => $schools->total(),
                    'icon' => 'bi-buildings',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Active students',
                    'value' => (int) Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-person-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Enrolled students',
                    'value' => (int) Student::query()
                        ->whereNull('deleted_at')
                        ->where('status', Student::STATUS_ACTIVE)
                        ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                            ->where('status', StudentEnrollment::STATUS_ACTIVE))
                        ->count(),
                    'icon' => 'bi-person-vcard',
                    'tone' => 'secondary',
                ],
            ],
            'schools' => $schools,
            'sections' => collect(),
            'canExport' => Gate::forUser($actor)->allows('reports.students.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function schoolReport(User $actor, array $filters): array
    {
        $authorizedSectionIds = $this->authorizedSectionIds($actor);
        $query = $this->scopedStudentQuery($actor);
        $this->applyStudentFilters($query, $actor, $filters, $authorizedSectionIds);

        $summaryQuery = clone $query;

        $students = $query
            ->with([
                'enrollments' => function (HasMany $enrollmentQuery) use ($actor): void {
                    $enrollmentQuery
                        ->where('status', StudentEnrollment::STATUS_ACTIVE)
                        ->with(['schoolClass', 'section'])
                        ->latest('enrollment_date');

                    if ($this->isTeacher($actor)) {
                        $teacher = $this->activeTeacherProfile($actor);
                        $this->applyTeacherEnrollmentScope($enrollmentQuery, $teacher);
                    }
                },
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('admission_no')
            ->paginate(15)
            ->withQueryString();

        return [
            'mode' => 'school',
            'title' => 'Student Reports',
            'description' => $actor->hasRoleCode(Role::ACCOUNTANT)
                ? 'Fee-context student lookup with privacy-safe identifiers only.'
                : 'School student enrollment summary with privacy-safe identifiers only.',
            'summary' => [
                [
                    'label' => 'Matching students',
                    'value' => $students->total(),
                    'icon' => 'bi-people',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Active students',
                    'value' => (clone $summaryQuery)->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-person-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'With active enrollment',
                    'value' => (clone $summaryQuery)
                        ->whereNull('deleted_at')
                        ->where('status', Student::STATUS_ACTIVE)
                        ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                            ->where('status', StudentEnrollment::STATUS_ACTIVE))
                        ->count(),
                    'icon' => 'bi-person-vcard',
                    'tone' => 'secondary',
                ],
            ],
            'students' => $students,
            'sections' => $this->sectionOptions($actor),
            'canExport' => Gate::forUser($actor)->allows('reports.students.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{search: ?string, date_from: ?string, date_to: ?string, state: ?string, section_id: ?int}
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = $this->reports->normalizeFilters($filters);

        $sectionId = filter_var($filters['section_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return [
            ...$normalized,
            'state' => filled($filters['state'] ?? null) ? (string) $filters['state'] : null,
            'section_id' => $sectionId === false ? null : $sectionId,
        ];
    }

    /**
     * @param  Builder<Student>  $query
     * @param  SupportCollection<int, int>  $authorizedSectionIds
     * @param  array<string, mixed>  $filters
     */
    private function applyStudentFilters(
        Builder $query,
        User $actor,
        array $filters,
        SupportCollection $authorizedSectionIds,
    ): void {
        if (filled($filters['section_id'] ?? null)) {
            $sectionId = (int) $filters['section_id'];

            if (! $authorizedSectionIds->contains($sectionId)) {
                throw (new ModelNotFoundException)->setModel(Section::class, [$sectionId]);
            }

            $query->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                ->where('status', StudentEnrollment::STATUS_ACTIVE)
                ->where('section_id', $sectionId));
        }

        if (! $this->isTeacher($actor)) {
            $query
                ->when(($filters['state'] ?? null) === Student::STATUS_ACTIVE, fn (Builder $builder) => $builder->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE))
                ->when(($filters['state'] ?? null) === Student::STATUS_INACTIVE, fn (Builder $builder) => $builder->whereNull('deleted_at')->where('status', Student::STATUS_INACTIVE))
                ->when(($filters['state'] ?? null) === Student::STATUS_TRANSFERRED, fn (Builder $builder) => $builder->whereNull('deleted_at')->where('status', Student::STATUS_TRANSFERRED))
                ->when(($filters['state'] ?? null) === Student::STATUS_GRADUATED, fn (Builder $builder) => $builder->whereNull('deleted_at')->where('status', Student::STATUS_GRADUATED))
                ->when(($filters['state'] ?? null) === 'archived', fn (Builder $builder) => $builder->whereNotNull('deleted_at'));
        }

        if (filled($filters['search'] ?? null)) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $nested) => $nested
                ->where('admission_no', 'like', $search)
                ->orWhere('first_name', 'like', $search)
                ->orWhere('last_name', 'like', $search));
        }
    }

    /**
     * @return Builder<Student>
     */
    private function scopedStudentQuery(User $actor): Builder
    {
        $query = Student::query()->select(self::PRIVACY_COLUMNS);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->applyTeacherAssignmentScope($query, $teacher);

            return $query;
        }

        if ($actor->hasRoleCode(Role::ACCOUNTANT)) {
            return $query
                ->whereHas('studentFees', fn (Builder $feeQuery) => $feeQuery
                    ->whereIn('status', [
                        StudentFee::STATUS_PENDING,
                        StudentFee::STATUS_PARTIAL,
                        StudentFee::STATUS_PAID,
                        StudentFee::STATUS_WAIVED,
                    ]));
        }

        return $query->withTrashed();
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
        if ($actor->isSuperAdmin()) {
            return collect();
        }

        $query = Section::query()
            ->with('schoolClass')
            ->orderBy('class_id')
            ->orderBy('name');

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query
                ->whereNull('deleted_at')
                ->where('status', Section::STATUS_ACTIVE)
                ->where('teacher_id', $teacher->id);
        } else {
            $query->withTrashed();
        }

        return $query->get();
    }

    /**
     * @param  Builder<Student>  $query
     */
    private function applyTeacherAssignmentScope(Builder $query, Teacher $teacher): void
    {
        $query
            ->where('status', Student::STATUS_ACTIVE)
            ->whereHas('enrollments', function (Builder $enrollmentQuery) use ($teacher): void {
                $enrollmentQuery->where('status', StudentEnrollment::STATUS_ACTIVE);
                $this->applyTeacherEnrollmentScope($enrollmentQuery, $teacher);
            });
    }

    /**
     * @param  Builder<StudentEnrollment>|HasMany<StudentEnrollment, Student>  $query
     */
    private function applyTeacherEnrollmentScope(Builder|HasMany $query, Teacher $teacher): void
    {
        $query
            ->whereHas('schoolClass', fn (Builder $classQuery) => $classQuery
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE))
            ->whereHas('section', fn (Builder $sectionQuery) => $sectionQuery
                ->whereNull('deleted_at')
                ->where('status', Section::STATUS_ACTIVE))
            ->where(function (Builder $nested) use ($teacher): void {
                $nested->whereHas('section', fn (Builder $sectionQuery) => $sectionQuery
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE)
                    ->where('teacher_id', $teacher->id))
                    ->orWhereHas('schoolClass.subjects', fn (Builder $subjectQuery) => $subjectQuery
                        ->whereNull('deleted_at')
                        ->where('status', Subject::STATUS_ACTIVE)
                        ->where('teacher_id', $teacher->id));
            });
    }

    private function authorizeCategory(User $actor): void
    {
        if (! Gate::forUser($actor)->allows('reports.students.view')) {
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

    private function isTeacher(User $actor): bool
    {
        return $actor->hasRoleCode(Role::TEACHER);
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
