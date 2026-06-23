<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSubjectService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, exam_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, ExamSubject>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', ExamSubject::class));

        return ExamSubject::query()
            ->with(['exam.academicYear', 'subject', 'schoolClass'])
            ->when(filled($filters['exam_id'] ?? null), fn ($query) => $query->where('exam_id', $filters['exam_id']))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('exam', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('subject', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('schoolClass', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(ExamSubject $examSubject, User $actor): ExamSubject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $examSubject));

        return $examSubject->load([
            'exam.academicYear',
            'subject',
            'schoolClass',
        ])->loadCount('examResults');
    }

    /**
     * @return array{
     *     exams: Collection<int, Exam>,
     *     classes: Collection<int, SchoolClass>,
     *     subjects: Collection<int, Subject>
     * }
     */
    public function assignmentOptions(User $actor, ?int $examId = null, ?int $classId = null): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', ExamSubject::class));

        $exams = Exam::query()
            ->with('academicYear')
            ->whereIn('status', [Exam::STATUS_SCHEDULED, Exam::STATUS_ONGOING])
            ->orderByDesc('start_date')
            ->get();

        $classes = SchoolClass::query()
            ->whereNull('deleted_at')
            ->where('status', SchoolClass::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subjects = new Collection;

        if ($classId !== null) {
            $subjects = Subject::query()
                ->whereNull('deleted_at')
                ->where('status', Subject::STATUS_ACTIVE)
                ->where('class_id', $classId)
                ->orderBy('name')
                ->get();
        }

        if ($examId !== null && ! $exams->contains('id', $examId)) {
            $examId = null;
        }

        return compact('exams', 'classes', 'subjects');
    }

    /**
     * @param  array{
     *     exam_id: int,
     *     class_id: int,
     *     subject_id: int,
     *     exam_date?: string|null,
     *     max_marks: numeric-string|float|int,
     *     passing_marks: numeric-string|float|int
     * }  $data
     */
    public function assign(array $data, User $actor): ExamSubject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', ExamSubject::class));

        return DB::transaction(function () use ($data, $actor): ExamSubject {
            $this->lockTenantSchool();

            $exam = Exam::query()
                ->lockForUpdate()
                ->with('academicYear')
                ->whereKey($data['exam_id'])
                ->whereIn('status', [Exam::STATUS_SCHEDULED, Exam::STATUS_ONGOING])
                ->first();

            if (! $exam instanceof Exam) {
                throw ValidationException::withMessages([
                    'exam_id' => 'Select a scheduled or ongoing Exam in the current school.',
                ]);
            }

            $schoolClass = SchoolClass::query()
                ->whereKey($data['class_id'])
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->first();

            if (! $schoolClass instanceof SchoolClass) {
                throw ValidationException::withMessages([
                    'class_id' => 'Select an active Class in the current school.',
                ]);
            }

            $subject = Subject::query()
                ->lockForUpdate()
                ->whereKey($data['subject_id'])
                ->whereNull('deleted_at')
                ->where('status', Subject::STATUS_ACTIVE)
                ->where('class_id', $schoolClass->id)
                ->first();

            if (! $subject instanceof Subject) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Select an active Subject that belongs to the selected Class.',
                ]);
            }

            if (ExamSubject::query()
                ->where('exam_id', $exam->id)
                ->where('subject_id', $subject->id)
                ->where('class_id', $schoolClass->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'subject_id' => 'This Subject and Class are already assigned to the selected Exam.',
                ]);
            }

            $maxMarks = $this->formatMarks($data['max_marks']);
            $passingMarks = $this->formatMarks($data['passing_marks']);

            if (bccomp($maxMarks, '0.01', 2) !== 1) {
                throw ValidationException::withMessages([
                    'max_marks' => 'Maximum marks must be greater than zero.',
                ]);
            }

            if (bccomp($passingMarks, '0.00', 2) === -1) {
                throw ValidationException::withMessages([
                    'passing_marks' => 'Passing marks cannot be negative.',
                ]);
            }

            if (bccomp($passingMarks, $maxMarks, 2) === 1) {
                throw ValidationException::withMessages([
                    'passing_marks' => 'Passing marks cannot exceed maximum marks.',
                ]);
            }

            $examSubject = ExamSubject::create([
                'exam_id' => $exam->id,
                'subject_id' => $subject->id,
                'class_id' => $schoolClass->id,
                'exam_date' => $data['exam_date'] ?? null,
                'max_marks' => $maxMarks,
                'passing_marks' => $passingMarks,
            ]);

            $this->logMutation($examSubject, $actor, 'assigned', [], $this->auditValues($examSubject));

            return $examSubject;
        });
    }

    private function formatMarks(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->hasRoleCode(Role::SCHOOL_ADMIN)
            && filled($actor->school_id)
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
    private function auditValues(ExamSubject $examSubject): array
    {
        return [
            'exam_id' => $examSubject->exam_id,
            'subject_id' => $examSubject->subject_id,
            'class_id' => $examSubject->class_id,
            'exam_date' => optional($examSubject->exam_date)?->toDateString(),
            'max_marks' => $examSubject->max_marks,
            'passing_marks' => $examSubject->passing_marks,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logMutation(
        ExamSubject $examSubject,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'examination_management', $action, $examSubject, 'Exam Subject '.$action.'.');
        $this->securityLogs->audit($actor, $examSubject, $action, $oldValues, $newValues);
    }
}
