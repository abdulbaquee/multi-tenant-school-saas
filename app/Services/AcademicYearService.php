<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, is_current?: string|null}  $filters
     * @return LengthAwarePaginator<int, AcademicYear>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', AcademicYear::class));

        return AcademicYear::query()
            ->with('school')
            ->withCount('terms')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where('name', 'like', $search);
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(array_key_exists('is_current', $filters) && $filters['is_current'] !== null, fn ($query) => $query->where('is_current', (bool) (int) $filters['is_current']))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(AcademicYear $academicYear, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $academicYear));

        return $academicYear->load([
            'school',
            'terms' => fn ($query) => $query->orderBy('term_order')->orderBy('start_date'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', AcademicYear::class));

        return DB::transaction(function () use ($data, $actor): AcademicYear {
            $this->lockTenantSchool();
            $this->validateNoOverlap($data['start_date'], $data['end_date']);

            $academicYear = AcademicYear::create([
                ...$data,
                'is_current' => false,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);

            $this->logMutation($academicYear, $actor, 'created', [], $this->auditValues($academicYear));

            return $academicYear;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AcademicYear $academicYear, array $data, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $academicYear));

        return DB::transaction(function () use ($academicYear, $data, $actor): AcademicYear {
            $this->lockTenantSchool();
            $lockedYear = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());
            $this->validateNoOverlap($data['start_date'], $data['end_date'], (int) $lockedYear->id);
            $this->validateTermsRemainInside($lockedYear, $data['start_date'], $data['end_date']);

            $oldValues = $this->auditValues($lockedYear);
            $lockedYear->fill($data)->save();
            $this->logChangedMutation($lockedYear, $actor, 'updated', $oldValues);

            return $lockedYear;
        });
    }

    public function makeCurrent(AcademicYear $academicYear, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('activate', $academicYear));

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $this->lockTenantSchool();
            $years = AcademicYear::query()->lockForUpdate()->orderBy('id')->get();
            $target = $years->firstWhere('id', $academicYear->getKey());

            if (! $target instanceof AcademicYear
                || $target->status !== AcademicYear::STATUS_ACTIVE
                || $target->is_current) {
                throw new AuthorizationException;
            }

            foreach ($years->where('is_current', true) as $previousCurrent) {
                $oldValues = $this->auditValues($previousCurrent);
                $previousCurrent->forceFill(['is_current' => false])->save();
                $this->logChangedMutation($previousCurrent, $actor, 'updated', $oldValues);
            }

            $oldValues = $this->auditValues($target);
            $target->forceFill(['is_current' => true])->save();
            $this->logChangedMutation($target, $actor, 'activated', $oldValues);

            return $target;
        });
    }

    public function deactivate(AcademicYear $academicYear, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('deactivate', $academicYear));

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $this->lockTenantSchool();
            $lockedYear = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());

            if ($lockedYear->is_current || $lockedYear->status !== AcademicYear::STATUS_ACTIVE) {
                throw new AuthorizationException;
            }

            if (AcademicTerm::query()
                ->where('academic_year_id', $lockedYear->id)
                ->where('status', AcademicTerm::STATUS_ACTIVE)
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Deactivate every active term before deactivating this academic year.',
                ]);
            }

            $oldValues = $this->auditValues($lockedYear);
            $lockedYear->forceFill(['status' => AcademicYear::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedYear, $actor, 'deactivated', $oldValues);

            return $lockedYear;
        });
    }

    public function reactivate(AcademicYear $academicYear, User $actor): AcademicYear
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('reactivate', $academicYear));

        return DB::transaction(function () use ($academicYear, $actor): AcademicYear {
            $this->lockTenantSchool();
            $lockedYear = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->getKey());

            if ($lockedYear->status !== AcademicYear::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            $this->validateNoOverlap(
                $lockedYear->start_date->toDateString(),
                $lockedYear->end_date->toDateString(),
                (int) $lockedYear->id,
            );

            $oldValues = $this->auditValues($lockedYear);
            $lockedYear->forceFill([
                'status' => AcademicYear::STATUS_ACTIVE,
                'is_current' => false,
            ])->save();
            $this->logChangedMutation($lockedYear, $actor, 'activated', $oldValues);

            return $lockedYear;
        });
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->canEstablishTenantContext() && ($actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $actor->hasRoleCode(Role::SCHOOL_ADMIN)
                && filled($actor->school_id)
                && $this->tenantContext->isTenant()
                && $this->tenantContext->tenantId() === (int) $actor->school_id);

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
        $schoolId = $this->tenantContext->tenantId();

        return School::query()
            ->whereKey($schoolId)
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function validateNoOverlap(string $startDate, string $endDate, ?int $exceptId = null): void
    {
        $start = CarbonImmutable::parse($startDate)->toDateString();
        $end = CarbonImmutable::parse($endDate)->toDateString();

        $overlaps = AcademicYear::query()
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'Academic year dates cannot overlap another academic year.',
            ]);
        }
    }

    private function validateTermsRemainInside(AcademicYear $academicYear, string $startDate, string $endDate): void
    {
        $outsideRange = AcademicTerm::query()
            ->where('academic_year_id', $academicYear->id)
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereDate('start_date', '<', $startDate)
                    ->orWhereDate('end_date', '>', $endDate);
            })
            ->exists();

        if ($outsideRange) {
            throw ValidationException::withMessages([
                'start_date' => 'The academic year dates must contain every retained term.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(AcademicYear $academicYear, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($academicYear);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $academicYear,
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
        AcademicYear $academicYear,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity(
            $actor,
            'academic_structure',
            $action,
            $academicYear,
            'Academic year '.$action.'.',
        );
        $this->securityLogs->audit($actor, $academicYear, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(AcademicYear $academicYear): array
    {
        return [
            'name' => $academicYear->name,
            'start_date' => $academicYear->start_date->toDateString(),
            'end_date' => $academicYear->end_date->toDateString(),
            'is_current' => (bool) $academicYear->is_current,
            'status' => $academicYear->status,
        ];
    }
}
