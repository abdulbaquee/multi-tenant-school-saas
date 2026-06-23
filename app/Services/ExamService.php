<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
        private readonly GradeScaleService $gradeScales,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, Exam>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Exam::class));

        return Exam::query()
            ->with(['academicYear', 'academicTerm'])
            ->withCount(['examSubjects', 'examResults', 'reportCards'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where('name', 'like', $search);
            })
            ->when(filled($filters['state'] ?? null), fn ($query) => $query->where('status', $filters['state']))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(Exam $exam, User $actor): Exam
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $exam));

        return $exam->load(['academicYear', 'academicTerm'])
            ->loadCount(['examSubjects', 'examResults', 'reportCards']);
    }

    /**
     * @return array{
     *     academicYears: Collection<int, AcademicYear>,
     *     terms: Collection<int, AcademicTerm>
     * }
     */
    public function formOptions(User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Exam::class));

        $academicYears = AcademicYear::query()
            ->where('status', AcademicYear::STATUS_ACTIVE)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->get();

        $terms = AcademicTerm::query()
            ->where('status', AcademicTerm::STATUS_ACTIVE)
            ->whereIn('academic_year_id', $academicYears->pluck('id'))
            ->orderBy('term_order')
            ->get();

        return compact('academicYears', 'terms');
    }

    /**
     * @param  array{
     *     academic_year_id: int,
     *     academic_term_id?: int|null,
     *     name: string,
     *     exam_type: string,
     *     start_date: string,
     *     end_date: string
     * }  $data
     */
    public function create(array $data, User $actor): Exam
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Exam::class));

        return DB::transaction(function () use ($data, $actor): Exam {
            $this->lockTenantSchool();
            $this->gradeScales->ensureDefaultScalesExist();
            $this->assertExamReferencesAreValid($data);
            $exam = Exam::create([
                ...$data,
                'status' => Exam::STATUS_SCHEDULED,
            ]);
            $this->logMutation($exam, $actor, 'created', [], $this->auditValues($exam));

            return $exam;
        });
    }

    /**
     * @param  array{
     *     exam_type: string,
     *     start_date: string,
     *     end_date: string
     * }  $data
     */
    public function update(Exam $exam, array $data, User $actor): Exam
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $exam));

        return DB::transaction(function () use ($exam, $data, $actor): Exam {
            $this->lockTenantSchool();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());

            if ($lockedExam->status !== Exam::STATUS_SCHEDULED) {
                throw ValidationException::withMessages([
                    'status' => 'Only scheduled exams can be edited.',
                ]);
            }

            $this->assertDateRange($data['start_date'], $data['end_date']);
            $oldValues = $this->auditValues($lockedExam);
            $lockedExam->fill($data)->save();
            $this->logChangedMutation($lockedExam, $actor, 'updated', $oldValues);

            return $lockedExam;
        });
    }

    public function publish(Exam $exam, User $actor): Exam
    {
        return $this->changeStatus(
            $exam,
            $actor,
            Exam::STATUS_SCHEDULED,
            Exam::STATUS_ONGOING,
            'publish',
            'published',
        );
    }

    public function complete(Exam $exam, User $actor): Exam
    {
        return $this->changeStatus(
            $exam,
            $actor,
            Exam::STATUS_ONGOING,
            Exam::STATUS_COMPLETED,
            'complete',
            'completed',
        );
    }

    public function cancel(Exam $exam, User $actor): Exam
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('cancel', $exam));

        return DB::transaction(function () use ($exam, $actor): Exam {
            $this->lockTenantSchool();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());

            if (! in_array($lockedExam->status, [Exam::STATUS_SCHEDULED, Exam::STATUS_ONGOING], true)) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedExam);
            $lockedExam->forceFill(['status' => Exam::STATUS_CANCELLED])->save();
            $this->logChangedMutation($lockedExam, $actor, 'cancelled', $oldValues);

            return $lockedExam;
        });
    }

    public function archive(Exam $exam, User $actor): Exam
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $exam));

        return DB::transaction(function () use ($exam, $actor): Exam {
            $this->lockTenantSchool();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());

            if ($lockedExam->examResults()->exists() || $lockedExam->reportCards()->exists()) {
                throw ValidationException::withMessages([
                    'exam' => 'Exams with retained results or report cards cannot be archived.',
                ]);
            }

            $oldValues = $this->auditValues($lockedExam);
            $lockedExam->delete();
            $this->logMutation($lockedExam, $actor, 'archived', $oldValues, $this->auditValues($lockedExam->fresh()));

            return $lockedExam->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertExamReferencesAreValid(array $data): void
    {
        $academicYear = AcademicYear::query()
            ->whereKey($data['academic_year_id'])
            ->where('status', AcademicYear::STATUS_ACTIVE)
            ->where('is_current', true)
            ->first();

        if (! $academicYear instanceof AcademicYear) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Select the current active Academic Year.',
            ]);
        }

        if (filled($data['academic_term_id'] ?? null)) {
            $term = AcademicTerm::query()
                ->whereKey($data['academic_term_id'])
                ->where('academic_year_id', $academicYear->id)
                ->where('status', AcademicTerm::STATUS_ACTIVE)
                ->first();

            if (! $term instanceof AcademicTerm) {
                throw ValidationException::withMessages([
                    'academic_term_id' => 'Select an active Academic Term in the selected Academic Year.',
                ]);
            }
        }

        $this->assertDateRange($data['start_date'], $data['end_date']);

        if ($data['start_date'] < $academicYear->start_date->toDateString()
            || $data['end_date'] > $academicYear->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'start_date' => 'Exam dates must fall within the selected Academic Year.',
            ]);
        }
    }

    private function assertDateRange(string $startDate, string $endDate): void
    {
        if ($endDate < $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after the start date.',
            ]);
        }
    }

    private function changeStatus(
        Exam $exam,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): Exam {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $exam));

        return DB::transaction(function () use ($exam, $actor, $from, $to, $action): Exam {
            $this->lockTenantSchool();
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->getKey());

            if ($lockedExam->status !== $from) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedExam);
            $lockedExam->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedExam, $actor, $action, $oldValues);

            return $lockedExam;
        });
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
    private function auditValues(Exam $exam): array
    {
        return [
            'academic_year_id' => $exam->academic_year_id,
            'academic_term_id' => $exam->academic_term_id,
            'name' => $exam->name,
            'exam_type' => $exam->exam_type,
            'start_date' => $exam->start_date?->toDateString(),
            'end_date' => $exam->end_date?->toDateString(),
            'status' => $exam->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(Exam $exam, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($exam);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $exam,
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
        Exam $exam,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'examination_management', $action, $exam, 'Exam '.$action.'.');
        $this->securityLogs->audit($actor, $exam, $action, $oldValues, $newValues);
    }
}
