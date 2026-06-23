<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamResultService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
        private readonly GradeScaleService $gradeScales,
    ) {}

    /**
     * @param  array{exam_subject_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function marksEntryWorkspace(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        if (! $actor->can('viewAny', ExamResult::class)) {
            throw new AuthorizationException('Exam marks entry authorization failed.');
        }

        $examSubjects = $this->operationalExamSubjectsQuery($actor)
            ->with(['exam', 'subject', 'schoolClass'])
            ->get();
        $selectedExamSubject = null;
        $roster = new Collection;
        $existing = collect();

        if (filled($filters['exam_subject_id'] ?? null)) {
            $selectedExamSubject = $this->findOperationalExamSubject((int) $filters['exam_subject_id'], $actor);
            $existing = ExamResult::query()
                ->where('exam_subject_id', $selectedExamSubject->id)
                ->get()
                ->keyBy('student_id');
            $roster = $this->eligibleEnrollments($selectedExamSubject);
        }

        return compact('examSubjects', 'selectedExamSubject', 'roster', 'existing');
    }

    /**
     * @param  array{exam_subject_id: int, entries: list<array{student_id: int, result_status: string, marks_obtained?: numeric-string|float|int|null, remarks?: string|null}>}  $data
     * @return array{created: int, corrected: int, unchanged: int}
     */
    public function saveMarksRoster(array $data, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('saveRoster', ExamResult::class));

        return DB::transaction(function () use ($data, $actor): array {
            $this->lockTenantSchool();
            $this->gradeScales->ensureDefaultScalesExist();
            $examSubject = $this->findOperationalExamSubject((int) $data['exam_subject_id'], $actor, true);
            $examSubject->loadMissing(['exam', 'subject', 'schoolClass']);
            $roster = $this->eligibleEnrollments($examSubject, true);
            $existing = ExamResult::query()
                ->where('exam_subject_id', $examSubject->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');

            if ($roster->isEmpty()) {
                throw ValidationException::withMessages([
                    'entries' => 'No eligible active Student Enrollments exist for this Exam Subject.',
                ]);
            }

            $desired = $this->desiredRoster($data, $roster, $examSubject);

            $created = 0;
            $corrected = 0;
            $unchanged = 0;

            foreach ($roster as $enrollment) {
                $values = $desired[$enrollment->student_id];
                $result = $existing->get($enrollment->student_id);

                if (! $result instanceof ExamResult) {
                    $result = ExamResult::create([
                        'exam_id' => $examSubject->exam_id,
                        'exam_subject_id' => $examSubject->id,
                        'student_id' => $enrollment->student_id,
                        'subject_id' => $examSubject->subject_id,
                        'marks_obtained' => $values['marks_obtained'],
                        'grade_scale_id' => $values['grade_scale_id'],
                        'result_status' => $values['result_status'],
                        'remarks' => $values['remarks'],
                        'entered_by' => $actor->id,
                    ]);
                    $this->logMutation($result, $actor, 'created', [], $this->auditValues($result));
                    $created++;

                    continue;
                }

                if ($this->resultChanged($result, $values)) {
                    $oldValues = $this->auditValues($result);
                    $result->fill([
                        'marks_obtained' => $values['marks_obtained'],
                        'grade_scale_id' => $values['grade_scale_id'],
                        'result_status' => $values['result_status'],
                        'remarks' => $values['remarks'],
                    ])->save();
                    $this->logChangedMutation($result, $actor, 'corrected', $oldValues);
                    $corrected++;

                    continue;
                }

                $unchanged++;
            }

            return compact('created', 'corrected', 'unchanged');
        });
    }

    /**
     * @param  array{search?: string|null, exam_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, ExamResult>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', ExamResult::class));

        return $this->scopedResultsQuery($actor)
            ->with(['exam', 'examSubject.schoolClass', 'subject', 'student', 'gradeScale'])
            ->when(filled($filters['exam_id'] ?? null), fn (Builder $query) => $query->where('exam_id', $filters['exam_id']))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('student', fn (Builder $query) => $query
                        ->where('admission_no', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search))
                        ->orWhereHas('exam', fn (Builder $query) => $query->where('name', 'like', $search))
                        ->orWhereHas('subject', fn (Builder $query) => $query->where('name', 'like', $search));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function examSummary(Exam $exam, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', ExamResult::class) && $this->canViewExam($actor, $exam));

        $exam->load(['academicYear'])->loadCount('examSubjects');
        $subjects = $this->examSubjectsForSummary($exam, $actor)
            ->with(['subject', 'schoolClass'])
            ->withCount('examResults')
            ->get();

        $results = $this->scopedResultsQuery($actor)->where('exam_id', $exam->id)->get();
        $summary = [
            'total' => $results->count(),
            'pass' => $results->where('result_status', ExamResult::STATUS_PASS)->count(),
            'fail' => $results->where('result_status', ExamResult::STATUS_FAIL)->count(),
            'absent' => $results->where('result_status', ExamResult::STATUS_ABSENT)->count(),
        ];

        return compact('exam', 'subjects', 'summary');
    }

    /**
     * @return array{processed: int}
     */
    public function processResults(Exam $exam, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('process', $exam));

        return DB::transaction(function () use ($exam, $actor): array {
            $this->lockTenantSchool();
            $this->gradeScales->ensureDefaultScalesExist();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());

            if ($lockedExam->status !== Exam::STATUS_ONGOING) {
                throw new AuthorizationException;
            }

            $processed = 0;

            ExamResult::query()
                ->where('exam_id', $lockedExam->id)
                ->lockForUpdate()
                ->with('examSubject')
                ->chunkById(100, function (Collection $results) use ($actor, &$processed): void {
                    foreach ($results as $result) {
                        if (! $result instanceof ExamResult || ! $result->examSubject instanceof ExamSubject) {
                            continue;
                        }

                        $oldValues = $this->auditValues($result);
                        $calculated = $result->result_status === ExamResult::STATUS_ABSENT
                            ? [
                                'marks_obtained' => '0.00',
                                'grade_scale_id' => null,
                                'result_status' => ExamResult::STATUS_ABSENT,
                            ]
                            : $this->gradeScales->calculateResultValues(
                                (string) $result->marks_obtained,
                                (string) $result->examSubject->max_marks,
                                (string) $result->examSubject->passing_marks,
                                ExamResult::STATUS_PASS,
                            );

                        $result->fill($calculated)->save();

                        if ($this->resultChangedFromAudit($oldValues, $this->auditValues($result))) {
                            $this->logChangedMutation($result, $actor, 'processed', $oldValues);
                            $processed++;
                        }
                    }
                });

            $this->securityLogs->activity($actor, 'examination_management', 'processed', $lockedExam, 'Exam results processed.');
            $this->securityLogs->audit($actor, $lockedExam, 'processed', [], ['processed_results' => $processed]);

            return ['processed' => $processed];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, StudentEnrollment>  $roster
     * @return array<int, array{marks_obtained: string, grade_scale_id: int|null, result_status: string, remarks: string|null}>
     */
    private function desiredRoster(array $data, Collection $roster, ExamSubject $examSubject): array
    {
        $entries = collect($data['entries'] ?? [])
            ->filter(fn (mixed $entry): bool => is_array($entry) && isset($entry['student_id']))
            ->keyBy(fn (array $entry): int => (int) $entry['student_id']);

        $expectedIds = $roster->pluck('student_id')->map(fn (mixed $id): int => (int) $id)->all();
        $submittedIds = $entries->keys()->all();

        if (count($expectedIds) !== count($submittedIds)
            || array_diff($expectedIds, $submittedIds) !== []
            || array_diff($submittedIds, $expectedIds) !== []) {
            throw ValidationException::withMessages([
                'entries' => 'Submit exactly one marks row for every eligible Student in the roster.',
            ]);
        }

        $desired = [];

        foreach ($roster as $enrollment) {
            $entry = $entries->get($enrollment->student_id);

            if (! is_array($entry)) {
                throw ValidationException::withMessages([
                    'entries' => 'Submit exactly one marks row for every eligible Student in the roster.',
                ]);
            }

            $isAbsent = filter_var($entry['absent'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || ($entry['result_status'] ?? null) === ExamResult::STATUS_ABSENT;

            if ($isAbsent) {
                $calculated = $this->gradeScales->calculateResultValues(
                    '0.00',
                    (string) $examSubject->max_marks,
                    (string) $examSubject->passing_marks,
                    ExamResult::STATUS_ABSENT,
                );
            } else {
                if (! array_key_exists('marks_obtained', $entry) || ! is_numeric($entry['marks_obtained'])) {
                    throw ValidationException::withMessages([
                        'entries' => 'Marks obtained is required for every non-absent Student.',
                    ]);
                }

                $calculated = $this->gradeScales->calculateResultValues(
                    number_format((float) $entry['marks_obtained'], 2, '.', ''),
                    (string) $examSubject->max_marks,
                    (string) $examSubject->passing_marks,
                    ExamResult::STATUS_PASS,
                );
            }

            $desired[$enrollment->student_id] = [
                ...$calculated,
                'remarks' => filled($entry['remarks'] ?? null) ? trim((string) $entry['remarks']) : null,
            ];
        }

        return $desired;
    }

    /**
     * @param  array{marks_obtained: string, grade_scale_id: int|null, result_status: string, remarks: string|null}  $values
     */
    private function resultChanged(ExamResult $result, array $values): bool
    {
        return (string) $result->marks_obtained !== $values['marks_obtained']
            || (int) $result->grade_scale_id !== (int) ($values['grade_scale_id'] ?? 0)
            || $result->result_status !== $values['result_status']
            || (string) ($result->remarks ?? '') !== (string) ($values['remarks'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function resultChangedFromAudit(array $oldValues, array $newValues): bool
    {
        return array_diff_assoc($newValues, $oldValues) !== [];
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    private function eligibleEnrollments(ExamSubject $examSubject, bool $lock = false): Collection
    {
        $examSubject->loadMissing('exam');
        $query = StudentEnrollment::query()
            ->with(['student'])
            ->where('academic_year_id', $examSubject->exam->academic_year_id)
            ->where('class_id', $examSubject->class_id)
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Student::STATUS_ACTIVE))
            ->orderBy('student_id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->sortBy(fn (StudentEnrollment $enrollment): string => $enrollment->student->admission_no)->values();
    }

    private function findOperationalExamSubject(int $examSubjectId, User $actor, bool $lock = false): ExamSubject
    {
        $query = $this->operationalExamSubjectsQuery($actor)
            ->with(['exam', 'subject', 'schoolClass'])
            ->whereKey($examSubjectId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $examSubject = $query->firstOrFail();

        if (! $actor->can('operate', [ExamResult::class, $examSubject])) {
            throw new AuthorizationException('Exam Subject authorization failed.');
        }

        return $examSubject;
    }

    /**
     * @return Builder<ExamSubject>
     */
    private function examSubjectsForSummary(Exam $exam, User $actor): Builder
    {
        $query = ExamSubject::query()
            ->where('exam_id', $exam->id)
            ->whereHas('exam', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->whereIn('status', [Exam::STATUS_ONGOING, Exam::STATUS_COMPLETED]))
            ->whereHas('subject', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Subject::STATUS_ACTIVE))
            ->whereHas('schoolClass', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE))
            ->orderByDesc('created_at');

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->authorize($teacher instanceof Teacher);
            $query->whereHas('subject', fn (Builder $query) => $query->where('teacher_id', $teacher->id));
        }

        return $query;
    }

    /**
     * @return Builder<ExamSubject>
     */
    private function operationalExamSubjectsQuery(User $actor): Builder
    {
        $query = ExamSubject::query()
            ->whereHas('exam', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Exam::STATUS_ONGOING))
            ->whereHas('subject', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Subject::STATUS_ACTIVE))
            ->whereHas('schoolClass', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE))
            ->orderByDesc('created_at');

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->authorize($teacher instanceof Teacher);
            $query->whereHas('subject', fn (Builder $query) => $query->where('teacher_id', $teacher->id));
        }

        return $query;
    }

    /**
     * @return Builder<ExamResult>
     */
    private function scopedResultsQuery(User $actor): Builder
    {
        $query = ExamResult::query();

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->authorize($teacher instanceof Teacher);
            $query->whereHas('examSubject.subject', fn (Builder $query) => $query->where('teacher_id', $teacher->id));
        }

        return $query;
    }

    private function canViewExam(User $actor, Exam $exam): bool
    {
        if ($actor->hasRoleCode(Role::SCHOOL_ADMIN)) {
            return (int) $actor->school_id === (int) $exam->school_id;
        }

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);

            return $teacher instanceof Teacher
                && ExamSubject::query()
                    ->where('exam_id', $exam->id)
                    ->whereHas('subject', fn (Builder $query) => $query->where('teacher_id', $teacher->id))
                    ->exists();
        }

        return false;
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

    private function authorizeActorContext(User $actor): void
    {
        $valid = filled($actor->school_id)
            && ($actor->hasRoleCode(Role::SCHOOL_ADMIN) || $actor->hasRoleCode(Role::TEACHER))
            && $actor->canEstablishTenantContext()
            && $this->tenantContext->isTenant()
            && $this->tenantContext->tenantId() === (int) $actor->school_id;

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ExamResult $examResult): array
    {
        return [
            'exam_id' => $examResult->exam_id,
            'exam_subject_id' => $examResult->exam_subject_id,
            'student_id' => $examResult->student_id,
            'subject_id' => $examResult->subject_id,
            'marks_obtained' => $examResult->marks_obtained,
            'grade_scale_id' => $examResult->grade_scale_id,
            'result_status' => $examResult->result_status,
            'remarks' => $examResult->remarks,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(ExamResult $examResult, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($examResult);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $examResult,
            $actor,
            $action,
            array_intersect_key($oldValues, array_flip($changedKeys)),
            array_intersect_key($newValues, array_flip($changedKeys)),
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logMutation(
        ExamResult $examResult,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'examination_management', $action, $examResult, 'Exam Result '.$action.'.');
        $this->securityLogs->audit($actor, $examResult, $action, $oldValues, $newValues);
    }
}
