<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    private const ENTRY_STATUSES = [
        Attendance::STATUS_PRESENT,
        Attendance::STATUS_ABSENT,
        Attendance::STATUS_LEAVE,
        Attendance::STATUS_LATE,
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function workspace(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        if (! $actor->can('viewAny', Attendance::class)) {
            throw new AuthorizationException('Attendance workspace authorization failed.');
        }

        $timezone = $this->schoolTimezone();
        $date = (string) ($filters['attendance_date'] ?? CarbonImmutable::now($timezone)->format('Y-m-d'));
        $currentYear = $this->currentAcademicYear(false);
        $sections = $this->activeSectionsQuery($actor)->get();
        $selectedClassId = filled($filters['class_id'] ?? null) ? (int) $filters['class_id'] : null;
        $selectedSection = null;
        $roster = new Collection;
        $existing = collect();

        if ($currentYear instanceof AcademicYear) {
            $this->validateAttendanceDate($date, $currentYear, $timezone);
        }

        if (filled($filters['section_id'] ?? null)) {
            if (! $currentYear instanceof AcademicYear) {
                throw ValidationException::withMessages([
                    'section_id' => 'Set exactly one active Academic Year as current before loading Attendance.',
                ]);
            }

            $selectedSection = $this->findOperationalSection((int) $filters['section_id'], $actor);

            if ($selectedClassId !== null && $selectedClassId !== (int) $selectedSection->class_id) {
                throw ValidationException::withMessages([
                    'section_id' => 'The selected Section must belong to the selected Class.',
                ]);
            }

            $selectedClassId = (int) $selectedSection->class_id;
            $existing = Attendance::query()
                ->where('academic_year_id', $currentYear->id)
                ->where('section_id', $selectedSection->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('student_id');
            $roster = $this->completeRoster(
                $this->eligibleEnrollments($currentYear, $selectedSection, $date),
                $existing,
                $currentYear,
                $selectedSection,
                $date,
            );
        }

        return [
            'currentYear' => $currentYear,
            'timezone' => $timezone,
            'attendanceDate' => $date,
            'sections' => $sections,
            'selectedClassId' => $selectedClassId,
            'selectedSection' => $selectedSection,
            'roster' => $roster,
            'existingAttendances' => $existing,
            'canMarkHoliday' => $actor->can('markHoliday', Attendance::class),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int, corrected: int, unchanged: int}
     */
    public function saveRoster(array $data, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('saveRoster', Attendance::class));

        return DB::transaction(function () use ($data, $actor): array {
            $this->lockTenantSchool();
            $currentYear = $this->currentAcademicYear(true, true);
            $section = $this->findOperationalSection((int) $data['section_id'], $actor, true);
            $timezone = $this->schoolTimezone();
            $date = (string) $data['attendance_date'];
            $this->validateAttendanceDate($date, $currentYear, $timezone);
            $activeRoster = $this->eligibleEnrollments($currentYear, $section, $date, true);
            $existing = Attendance::query()
                ->where('academic_year_id', $currentYear->id)
                ->where('section_id', $section->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');
            $roster = $this->completeRoster(
                $activeRoster,
                $existing,
                $currentYear,
                $section,
                $date,
                true,
            );

            if ($roster->isEmpty()) {
                throw ValidationException::withMessages([
                    'entries' => 'No eligible active or retained Student Enrollments exist for this Section and date.',
                ]);
            }

            $desired = $this->desiredRoster($data, $roster, $actor);

            $needsCreate = $roster->contains(fn (StudentEnrollment $enrollment): bool => ! $existing->has($enrollment->student_id));
            $needsUpdate = $roster->contains(function (StudentEnrollment $enrollment) use ($desired, $existing): bool {
                $attendance = $existing->get($enrollment->student_id);

                return $attendance instanceof Attendance
                    && $this->attendanceChanged($attendance, $desired[$enrollment->student_id]);
            });
            $hasHolidayTransition = $roster->contains(function (StudentEnrollment $enrollment) use ($desired, $existing): bool {
                $attendance = $existing->get($enrollment->student_id);

                return $attendance instanceof Attendance
                    && $attendance->status !== $desired[$enrollment->student_id]['status']
                    && ($attendance->status === Attendance::STATUS_HOLIDAY
                        || $desired[$enrollment->student_id]['status'] === Attendance::STATUS_HOLIDAY);
            });

            $this->authorize(! $needsCreate || $actor->hasPermission('attendance.create'));
            $this->authorize(! $needsUpdate || $actor->hasPermission('attendance.update'));
            $this->authorize(! $hasHolidayTransition || $actor->can('markHoliday', Attendance::class));

            $created = 0;
            $corrected = 0;
            $unchanged = 0;
            $audits = [];

            foreach ($roster as $enrollment) {
                $values = $desired[$enrollment->student_id];
                $attendance = $existing->get($enrollment->student_id);

                if (! $attendance instanceof Attendance) {
                    try {
                        $attendance = Attendance::create([
                            'student_id' => $enrollment->student_id,
                            'academic_year_id' => $currentYear->id,
                            'class_id' => $section->class_id,
                            'section_id' => $section->id,
                            'attendance_date' => $date,
                            'status' => $values['status'],
                            'remarks' => $values['remarks'],
                            'marked_by' => $actor->id,
                        ]);
                    } catch (QueryException $exception) {
                        if (! $this->isConstraintViolation($exception)) {
                            throw $exception;
                        }

                        throw ValidationException::withMessages([
                            'entries' => 'Attendance changed concurrently. Reload the roster and try again.',
                        ]);
                    }

                    $created++;
                    $audits[] = [
                        'attendance' => $attendance,
                        'event' => 'created',
                        'old' => [],
                        'new' => $this->auditValues($attendance, $enrollment->id, filled($values['remarks'])),
                    ];

                    continue;
                }

                if (! $this->attendanceChanged($attendance, $values)) {
                    $unchanged++;

                    continue;
                }

                $remarksChanged = $this->normalizeRemarks($attendance->remarks) !== $values['remarks'];
                $oldValues = $this->auditValues($attendance, $enrollment->id, false);
                $attendance->forceFill($values)->save();
                $corrected++;
                $audits[] = [
                    'attendance' => $attendance,
                    'event' => 'corrected',
                    'old' => $oldValues,
                    'new' => $this->auditValues($attendance, $enrollment->id, $remarksChanged),
                ];
            }

            if ($audits !== []) {
                $this->securityLogs->activity(
                    $actor,
                    'attendance_management',
                    'saved',
                    $section,
                    'Attendance roster saved.',
                );

                foreach ($audits as $audit) {
                    $this->securityLogs->audit(
                        $actor,
                        $audit['attendance'],
                        $audit['event'],
                        $audit['old'],
                        $audit['new'],
                    );
                }
            }

            return compact('created', 'corrected', 'unchanged');
        });
    }

    public function correctionForm(Attendance $attendance, User $actor): Attendance
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $attendance));
        $this->authorizeCurrentYearCorrection($attendance);

        return $attendance->load(['student', 'academicYear', 'schoolClass', 'section', 'markedBy']);
    }

    /**
     * @param  array{status: string, remarks?: string|null}  $data
     */
    public function correct(Attendance $attendance, array $data, User $actor): Attendance
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $attendance));

        return DB::transaction(function () use ($attendance, $data, $actor): Attendance {
            $this->lockTenantSchool();
            $lockedAttendance = Attendance::query()->lockForUpdate()->findOrFail($attendance->getKey());
            $this->authorize($actor->can('update', $lockedAttendance));
            $this->authorizeCurrentYearCorrection($lockedAttendance, true);

            if (! isset($data['status']) || ! is_string($data['status'])) {
                throw ValidationException::withMessages(['status' => 'Select an allowed Attendance status.']);
            }

            $status = $data['status'];
            $rawRemarks = $data['remarks'] ?? null;

            if ($rawRemarks !== null && ! is_string($rawRemarks)) {
                throw ValidationException::withMessages(['remarks' => 'Attendance remarks must be text.']);
            }

            $remarks = $this->normalizeRemarks($rawRemarks);

            if ($remarks !== null && mb_strlen($remarks) > 500) {
                throw ValidationException::withMessages(['remarks' => 'Attendance remarks may not exceed 500 characters.']);
            }

            if (! in_array($status, [...self::ENTRY_STATUSES, Attendance::STATUS_HOLIDAY], true)) {
                throw ValidationException::withMessages(['status' => 'Select an allowed Attendance status.']);
            }

            if ($actor->hasRoleCode(Role::TEACHER)
                && ($status === Attendance::STATUS_HOLIDAY
                    || $lockedAttendance->status === Attendance::STATUS_HOLIDAY)) {
                throw new AuthorizationException;
            }

            if ($status !== $lockedAttendance->status
                && ($status === Attendance::STATUS_HOLIDAY
                    || $lockedAttendance->status === Attendance::STATUS_HOLIDAY)) {
                throw ValidationException::withMessages([
                    'status' => 'Use the complete-roster workflow to change a Section Holiday status.',
                ]);
            }

            if ($lockedAttendance->status === $status
                && $this->normalizeRemarks($lockedAttendance->remarks) === $remarks) {
                return $lockedAttendance;
            }

            $remarksChanged = $this->normalizeRemarks($lockedAttendance->remarks) !== $remarks;
            $oldValues = $this->auditValues($lockedAttendance, null, false);
            $lockedAttendance->forceFill(compact('status', 'remarks'))->save();
            $this->securityLogs->activity(
                $actor,
                'attendance_management',
                'corrected',
                $lockedAttendance,
                'Attendance corrected.',
            );
            $this->securityLogs->audit(
                $actor,
                $lockedAttendance,
                'corrected',
                $oldValues,
                $this->auditValues($lockedAttendance, null, $remarksChanged),
            );

            return $lockedAttendance;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{attendances: LengthAwarePaginator, sections: Collection<int, Section>}
     */
    public function history(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->loadAuthorizationContext($actor);
        $this->authorize($actor->can('viewAny', Attendance::class));

        $sectionIds = $this->historySectionIds($actor);
        $query = Attendance::query()
            ->with(['student', 'academicYear', 'schoolClass', 'section.schoolClass', 'markedBy'])
            ->when($actor->hasRoleCode(Role::TEACHER), fn (Builder $builder) => $builder->whereIn('section_id', $sectionIds));

        if (filled($filters['section_id'] ?? null)) {
            $sectionId = (int) $filters['section_id'];

            if (! $sectionIds->contains($sectionId)) {
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

        return [
            'attendances' => $query
                ->orderByDesc('attendance_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'sections' => $this->historySectionsQuery($actor)->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function monthlySummary(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Attendance::class));

        $timezone = $this->schoolTimezone();
        $month = (string) ($filters['month'] ?? CarbonImmutable::now($timezone)->format('Y-m'));
        $monthStart = $this->parseMonth($month, $timezone);
        $sections = $this->activeSectionsQuery($actor)->get();
        $selectedSection = null;
        $counts = array_fill_keys([
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_LEAVE,
            Attendance::STATUS_LATE,
            Attendance::STATUS_HOLIDAY,
        ], 0);
        $attendanceDays = 0;

        if (filled($filters['section_id'] ?? null)) {
            $selectedSection = $this->findOperationalSection((int) $filters['section_id'], $actor);
            $baseQuery = Attendance::query()
                ->where('section_id', $selectedSection->id)
                ->whereBetween('attendance_date', [
                    $monthStart->startOfMonth()->format('Y-m-d'),
                    $monthStart->endOfMonth()->format('Y-m-d'),
                ]);

            $baseQuery->clone()
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->each(function (int|string $count, string $status) use (&$counts): void {
                    if (array_key_exists($status, $counts)) {
                        $counts[$status] = (int) $count;
                    }
                });

            $attendanceDays = (int) $baseQuery->distinct()->count('attendance_date');
        }

        return compact('month', 'sections', 'selectedSection', 'counts', 'attendanceDays');
    }

    private function activeSectionsQuery(User $actor): Builder
    {
        $query = Section::query()
            ->with('schoolClass')
            ->whereNull('deleted_at')
            ->where('status', Section::STATUS_ACTIVE)
            ->whereHas('schoolClass', fn (Builder $classQuery) => $classQuery
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE));

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->authorize($teacher instanceof Teacher);
            $query->where('teacher_id', $teacher->id);
        }

        return $query
            ->orderBy('class_id')
            ->orderBy('name');
    }

    private function historySectionsQuery(User $actor): Builder
    {
        if ($actor->hasRoleCode(Role::TEACHER)) {
            return $this->activeSectionsQuery($actor);
        }

        return Section::withTrashed()
            ->with('schoolClass')
            ->orderBy('class_id')
            ->orderBy('name');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function historySectionIds(User $actor): \Illuminate\Support\Collection
    {
        return $this->historySectionsQuery($actor)->pluck('id')->map(fn (mixed $id): int => (int) $id);
    }

    private function findOperationalSection(int $sectionId, User $actor, bool $lock = false): Section
    {
        $query = $this->activeSectionsQuery($actor)->whereKey($sectionId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $section = $query->firstOrFail();
        if (! $actor->can('operate', [Attendance::class, $section])) {
            throw new AuthorizationException('Attendance Section authorization failed.');
        }

        return $section;
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    private function eligibleEnrollments(
        AcademicYear $academicYear,
        Section $section,
        string $date,
        bool $lock = false,
    ): Collection {
        $query = StudentEnrollment::query()
            ->with('student')
            ->where('academic_year_id', $academicYear->id)
            ->where('class_id', $section->class_id)
            ->where('section_id', $section->id)
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereDate('enrollment_date', '<=', $date)
            ->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                ->whereNull('deleted_at')
                ->where('status', Student::STATUS_ACTIVE))
            ->orderBy('roll_no');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * Merge the active roster with already-retained rows for the selected date.
     * Retained rows may be corrected after later Student or Enrollment lifecycle
     * changes, but this method never creates a new ineligible roster member.
     *
     * @param  Collection<int, StudentEnrollment>  $activeRoster
     * @param  Collection<int, Attendance>  $existing
     * @return Collection<int, StudentEnrollment>
     */
    private function completeRoster(
        Collection $activeRoster,
        Collection $existing,
        AcademicYear $academicYear,
        Section $section,
        string $date,
        bool $lock = false,
    ): Collection {
        $activeStudentIds = $activeRoster->pluck('student_id')->map(fn (mixed $id): int => (int) $id);
        $retainedStudentIds = $existing->keys()
            ->map(fn (mixed $id): int => (int) $id)
            ->diff($activeStudentIds)
            ->values();

        if ($retainedStudentIds->isEmpty()) {
            return $activeRoster;
        }

        $query = StudentEnrollment::query()
            ->with('student')
            ->where('academic_year_id', $academicYear->id)
            ->where('class_id', $section->class_id)
            ->where('section_id', $section->id)
            ->whereIn('student_id', $retainedStudentIds)
            ->whereDate('enrollment_date', '<=', $date)
            ->orderBy('roll_no');

        if ($lock) {
            $query->lockForUpdate();
        }

        $retainedRoster = $query->get();
        $resolvedStudentIds = $retainedRoster->pluck('student_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values();

        if ($resolvedStudentIds->all() !== $retainedStudentIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'entries' => 'A retained Attendance row no longer matches its immutable Enrollment placement.',
            ]);
        }

        return $activeRoster
            ->merge($retainedRoster)
            ->sortBy('roll_no', SORT_NATURAL)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, StudentEnrollment>  $roster
     * @return array<int, array{status: string, remarks: string|null}>
     */
    private function desiredRoster(array $data, Collection $roster, User $actor): array
    {
        if (($data['mode'] ?? null) === 'holiday') {
            $this->authorize($actor->can('markHoliday', Attendance::class));

            return $roster->mapWithKeys(fn (StudentEnrollment $enrollment): array => [
                $enrollment->student_id => [
                    'status' => Attendance::STATUS_HOLIDAY,
                    'remarks' => null,
                ],
            ])->all();
        }

        if (($data['mode'] ?? null) !== 'roster' || ! is_array($data['entries'] ?? null)) {
            throw ValidationException::withMessages(['mode' => 'Select a valid Attendance save mode.']);
        }

        $desired = [];

        foreach ($data['entries'] as $entry) {
            if (! is_array($entry) || ! isset($entry['student_id'], $entry['status'])) {
                throw ValidationException::withMessages(['entries' => 'Every roster row requires a Student and status.']);
            }

            $studentId = (int) $entry['student_id'];

            if (array_key_exists($studentId, $desired)) {
                throw ValidationException::withMessages(['entries' => 'Duplicate Students are not allowed in the Attendance roster.']);
            }

            $status = (string) $entry['status'];

            if (! in_array($status, self::ENTRY_STATUSES, true)) {
                throw ValidationException::withMessages(['entries' => 'Select an allowed status for every Student.']);
            }

            $remarks = $this->normalizeRemarks($entry['remarks'] ?? null);

            if ($remarks !== null && mb_strlen($remarks) > 500) {
                throw ValidationException::withMessages(['entries' => 'Attendance remarks may not exceed 500 characters.']);
            }

            $desired[$studentId] = compact('status', 'remarks');
        }

        $eligibleIds = $roster->pluck('student_id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();
        $submittedIds = collect(array_keys($desired))->sort()->values()->all();

        if ($eligibleIds !== $submittedIds) {
            throw ValidationException::withMessages([
                'entries' => 'Submit exactly one status for every eligible Student in the current roster.',
            ]);
        }

        return $desired;
    }

    /**
     * @param  array{status: string, remarks: string|null}  $values
     */
    private function attendanceChanged(Attendance $attendance, array $values): bool
    {
        return $attendance->status !== $values['status']
            || $this->normalizeRemarks($attendance->remarks) !== $values['remarks'];
    }

    private function currentAcademicYear(bool $required, bool $lock = false): ?AcademicYear
    {
        $query = AcademicYear::query()
            ->where('status', AcademicYear::STATUS_ACTIVE)
            ->where('is_current', true)
            ->limit(2);

        if ($lock) {
            $query->lockForUpdate();
        }

        $years = $query->get();

        if ($years->count() === 1) {
            return $years->first();
        }

        if ($required) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Set exactly one active Academic Year as current before saving Attendance.',
            ]);
        }

        return null;
    }

    private function authorizeCurrentYearCorrection(Attendance $attendance, bool $lock = false): void
    {
        $currentYear = $this->currentAcademicYear(true, $lock);

        if ((int) $attendance->academic_year_id !== (int) $currentYear->id) {
            throw ValidationException::withMessages([
                'attendance' => 'Historical Academic Year Attendance is read-only.',
            ]);
        }
    }

    private function validateAttendanceDate(string $date, AcademicYear $academicYear, string $timezone): void
    {
        try {
            $attendanceDate = CarbonImmutable::createFromFormat('Y-m-d', $date, $timezone)?->startOfDay();
        } catch (InvalidFormatException) {
            $attendanceDate = null;
        }

        if (! $attendanceDate instanceof CarbonImmutable || $attendanceDate->format('Y-m-d') !== $date) {
            throw ValidationException::withMessages(['attendance_date' => 'Enter a valid Attendance date.']);
        }

        if ($attendanceDate->lt($academicYear->start_date)
            || $attendanceDate->gt($academicYear->end_date)
            || $attendanceDate->gt(CarbonImmutable::now($timezone)->startOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance date must be inside the current Academic Year and not in the future.',
            ]);
        }
    }

    private function parseMonth(string $month, string $timezone): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('Y-m', $month, $timezone)?->startOfMonth();
        } catch (InvalidFormatException) {
            $date = null;
        }

        if (! $date instanceof CarbonImmutable || $date->format('Y-m') !== $month) {
            throw ValidationException::withMessages(['month' => 'Enter a valid summary month.']);
        }

        return $date;
    }

    private function schoolTimezone(): string
    {
        return (string) (SchoolSetting::query()->value('timezone') ?: config('app.timezone', 'UTC'));
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function activeTeacherProfile(User $actor): ?Teacher
    {
        if (! $actor->hasRoleCode(Role::TEACHER) || ! filled($actor->school_id)) {
            return null;
        }

        return $actor->teacherProfile()
            ->whereNull('deleted_at')
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
    }

    private function loadAuthorizationContext(User $actor): void
    {
        $actor->loadMissing(['role.permissions', 'school', 'teacherProfile']);
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->canEstablishTenantContext()
            && filled($actor->school_id)
            && $this->tenantContext->isTenant()
            && $this->tenantContext->tenantId() === (int) $actor->school_id
            && in_array($actor->role?->code, [Role::SCHOOL_ADMIN, Role::TEACHER], true);

        if (! $valid) {
            throw new AuthorizationException('Attendance actor context mismatch.');
        }
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function normalizeRemarks(mixed $remarks): ?string
    {
        if (! filled($remarks)) {
            return null;
        }

        return trim((string) $remarks);
    }

    private function isConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000'], true);
    }

    /**
     * @return array<string, int|string|bool>
     */
    private function auditValues(Attendance $attendance, ?int $enrollmentId, bool $remarksChanged): array
    {
        return array_filter([
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'student_enrollment_id' => $enrollmentId,
            'academic_year_id' => $attendance->academic_year_id,
            'class_id' => $attendance->class_id,
            'section_id' => $attendance->section_id,
            'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
            'status' => $attendance->status,
            'remarks_changed' => $remarksChanged,
        ], fn (mixed $value): bool => $value !== null);
    }
}
