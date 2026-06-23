<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReportCardService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
        private readonly GradeScaleService $gradeScales,
    ) {}

    /**
     * @param  array{search?: string|null, exam_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, ReportCard>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', ReportCard::class));

        return $this->scopedReportCardsQuery($actor)
            ->with(['exam', 'student', 'schoolClass', 'section', 'gradeScale'])
            ->when(filled($filters['exam_id'] ?? null), fn (Builder $query) => $query->where('exam_id', $filters['exam_id']))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('student', fn (Builder $query) => $query
                        ->where('admission_no', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search))
                        ->orWhereHas('exam', fn (Builder $query) => $query->where('name', 'like', $search));
                });
            })
            ->orderByDesc('generated_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailFor(ReportCard $reportCard, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $reportCard));

        $reportCard->load([
            'exam.academicYear',
            'student',
            'schoolClass',
            'section',
            'gradeScale',
            'generatedBy',
        ]);

        return [
            'reportCard' => $reportCard,
            'subjectResults' => $this->subjectResultsFor($reportCard, $actor),
        ];
    }

    /**
     * @return array{created: int, regenerated: int, skipped: int}
     */
    public function generateForExam(Exam $exam, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('generate', $exam));

        return DB::transaction(function () use ($exam, $actor): array {
            $this->lockTenantSchool();
            $this->gradeScales->ensureDefaultScalesExist();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());
            $lockedExam->loadMissing('academicYear');

            if (! in_array($lockedExam->status, [Exam::STATUS_ONGOING, Exam::STATUS_COMPLETED], true)) {
                throw new AuthorizationException;
            }

            $created = 0;
            $regenerated = 0;
            $skipped = 0;

            foreach ($this->eligibleEnrollments($lockedExam) as $enrollment) {
                $summary = $this->buildStudentSummary($lockedExam, $enrollment);

                if ($summary === null) {
                    $skipped++;

                    continue;
                }

                $existing = ReportCard::query()
                    ->where('exam_id', $lockedExam->id)
                    ->where('student_id', $enrollment->student_id)
                    ->lockForUpdate()
                    ->first();

                if (! $existing instanceof ReportCard) {
                    $reportCard = ReportCard::create([
                        'exam_id' => $lockedExam->id,
                        'student_id' => $enrollment->student_id,
                        'academic_year_id' => $lockedExam->academic_year_id,
                        'class_id' => $enrollment->class_id,
                        'section_id' => $enrollment->section_id,
                        ...$summary,
                        'generated_at' => now(),
                        'generated_by' => $actor->id,
                    ]);
                    $this->logMutation($reportCard, $actor, 'generated', [], $this->auditValues($reportCard));
                    $created++;

                    continue;
                }

                $oldValues = $this->auditValues($existing);
                $existing->fill([
                    ...$summary,
                    'generated_at' => now(),
                ])->save();

                if ($this->summaryChanged($oldValues, $this->auditValues($existing))) {
                    $this->logChangedMutation($existing, $actor, 'regenerated', $oldValues);
                    $regenerated++;
                } else {
                    $skipped++;
                }
            }

            $this->securityLogs->activity($actor, 'examination_management', 'generated', $lockedExam, 'Exam report cards generated.');
            $this->securityLogs->audit($actor, $lockedExam, 'generated', [], [
                'created' => $created,
                'regenerated' => $regenerated,
                'skipped' => $skipped,
            ]);

            return compact('created', 'regenerated', 'skipped');
        });
    }

    /**
     * @return Collection<int, ExamResult>
     */
    private function subjectResultsFor(ReportCard $reportCard, User $actor): Collection
    {
        return ExamResult::query()
            ->with(['subject', 'examSubject', 'gradeScale'])
            ->where('exam_id', $reportCard->exam_id)
            ->where('student_id', $reportCard->student_id)
            ->get()
            ->sortBy(fn (ExamResult $result): string => $result->subject->name)
            ->values();
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    private function eligibleEnrollments(Exam $exam): Collection
    {
        $classIds = ExamSubject::query()
            ->where('exam_id', $exam->id)
            ->distinct()
            ->pluck('class_id');

        return StudentEnrollment::query()
            ->with(['student'])
            ->where('academic_year_id', $exam->academic_year_id)
            ->whereIn('class_id', $classIds)
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Student::STATUS_ACTIVE))
            ->orderBy('student_id')
            ->get()
            ->unique('student_id')
            ->values();
    }

    /**
     * @return array{total_marks: string, marks_obtained: string, percentage: string, grade_scale_id: int|null, result_status: string}|null
     */
    private function buildStudentSummary(Exam $exam, StudentEnrollment $enrollment): ?array
    {
        $examSubjects = ExamSubject::query()
            ->where('exam_id', $exam->id)
            ->where('class_id', $enrollment->class_id)
            ->get();

        if ($examSubjects->isEmpty()) {
            return null;
        }

        $results = ExamResult::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $enrollment->student_id)
            ->whereIn('exam_subject_id', $examSubjects->pluck('id'))
            ->get()
            ->keyBy('exam_subject_id');

        if ($results->count() !== $examSubjects->count()) {
            return null;
        }

        $totalMarks = '0.00';
        $marksObtained = '0.00';
        $hasFail = false;
        $hasAbsent = false;

        foreach ($examSubjects as $examSubject) {
            $result = $results->get($examSubject->id);

            if (! $result instanceof ExamResult) {
                return null;
            }

            $totalMarks = bcadd($totalMarks, (string) $examSubject->max_marks, 2);
            $marksObtained = bcadd($marksObtained, (string) $result->marks_obtained, 2);

            if ($result->result_status === ExamResult::STATUS_FAIL) {
                $hasFail = true;
            }

            if ($result->result_status === ExamResult::STATUS_ABSENT) {
                $hasAbsent = true;
            }
        }

        if ($hasFail || $hasAbsent) {
            $resultStatus = ReportCard::STATUS_FAIL;
        } else {
            $resultStatus = ReportCard::STATUS_PASS;
        }

        $percentage = bccomp($totalMarks, '0.00', 2) === 0
            ? '0.00'
            : bcmul(bcdiv($marksObtained, $totalMarks, 4), '100', 2);
        $gradeScale = $this->gradeScales->resolveForPercentage($percentage);

        return [
            'total_marks' => $totalMarks,
            'marks_obtained' => $marksObtained,
            'percentage' => $percentage,
            'grade_scale_id' => $gradeScale?->id,
            'result_status' => $resultStatus,
        ];
    }

    /**
     * @return Builder<ReportCard>
     */
    private function scopedReportCardsQuery(User $actor): Builder
    {
        $query = ReportCard::query();

        if ($actor->hasRoleCode(Role::TEACHER)) {
            $teacher = $this->activeTeacherProfile($actor);
            $this->authorize($teacher instanceof Teacher);
            $query->whereHas('exam.examResults', function (Builder $query) use ($teacher): void {
                $query->whereColumn('exam_results.student_id', 'report_cards.student_id')
                    ->whereColumn('exam_results.exam_id', 'report_cards.exam_id')
                    ->whereHas('examSubject.subject', fn (Builder $query) => $query->where('teacher_id', $teacher->id));
            });
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

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function summaryChanged(array $oldValues, array $newValues): bool
    {
        return array_diff_assoc($newValues, $oldValues) !== [];
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
    private function auditValues(ReportCard $reportCard): array
    {
        return [
            'exam_id' => $reportCard->exam_id,
            'student_id' => $reportCard->student_id,
            'total_marks' => $reportCard->total_marks,
            'marks_obtained' => $reportCard->marks_obtained,
            'percentage' => $reportCard->percentage,
            'grade_scale_id' => $reportCard->grade_scale_id,
            'result_status' => $reportCard->result_status,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(ReportCard $reportCard, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($reportCard);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $reportCard,
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
        ReportCard $reportCard,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'examination_management', $action, $reportCard, 'Report Card '.$action.'.');
        $this->securityLogs->audit($actor, $reportCard, $action, $oldValues, $newValues);
    }
}
