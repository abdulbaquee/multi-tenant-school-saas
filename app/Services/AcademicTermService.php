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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicTermService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null, academic_year_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, AcademicTerm>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', AcademicTerm::class));

        return AcademicTerm::query()
            ->with(['academicYear', 'school'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.trim((string) $filters['search']).'%');
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['academic_year_id'] ?? null), fn ($query) => $query->where('academic_year_id', $filters['academic_year_id']))
            ->orderByDesc('start_date')
            ->orderBy('term_order')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(AcademicTerm $academicTerm, User $actor): AcademicTerm
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $academicTerm));

        return $academicTerm->load(['academicYear', 'school']);
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    public function availableYearsFor(User $actor, bool $activeOnly = false): Collection
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', AcademicYear::class));

        return AcademicYear::query()
            ->when($activeOnly, fn ($query) => $query->where('status', AcademicYear::STATUS_ACTIVE))
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AcademicTerm
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', AcademicTerm::class));

        return DB::transaction(function () use ($data, $actor): AcademicTerm {
            $this->lockTenantSchool();
            $academicYear = $this->activeYearForMutation((int) $data['academic_year_id']);
            $this->validateDateContract($academicYear, $data['start_date'], $data['end_date']);
            $this->validateNoOverlap($academicYear, $data['start_date'], $data['end_date']);

            $academicTerm = AcademicTerm::create([
                ...$data,
                'status' => AcademicTerm::STATUS_ACTIVE,
            ]);

            $this->logMutation($academicTerm, $actor, 'created', [], $this->auditValues($academicTerm));

            return $academicTerm;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AcademicTerm $academicTerm, array $data, User $actor): AcademicTerm
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $academicTerm));

        return DB::transaction(function () use ($academicTerm, $data, $actor): AcademicTerm {
            $this->lockTenantSchool();
            $lockedTerm = AcademicTerm::query()->lockForUpdate()->findOrFail($academicTerm->getKey());
            $academicYear = $this->activeYearForMutation((int) $data['academic_year_id']);
            $this->validateDateContract($academicYear, $data['start_date'], $data['end_date']);
            $this->validateNoOverlap($academicYear, $data['start_date'], $data['end_date'], (int) $lockedTerm->id);

            $oldValues = $this->auditValues($lockedTerm);
            $lockedTerm->fill($data)->save();
            $this->logChangedMutation($lockedTerm, $actor, 'updated', $oldValues);

            return $lockedTerm;
        });
    }

    public function deactivate(AcademicTerm $academicTerm, User $actor): AcademicTerm
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('deactivate', $academicTerm));

        return DB::transaction(function () use ($academicTerm, $actor): AcademicTerm {
            $this->lockTenantSchool();
            $lockedTerm = AcademicTerm::query()->lockForUpdate()->findOrFail($academicTerm->getKey());

            if ($lockedTerm->status !== AcademicTerm::STATUS_ACTIVE) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedTerm);
            $lockedTerm->forceFill(['status' => AcademicTerm::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedTerm, $actor, 'deactivated', $oldValues);

            return $lockedTerm;
        });
    }

    public function reactivate(AcademicTerm $academicTerm, User $actor): AcademicTerm
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('reactivate', $academicTerm));

        return DB::transaction(function () use ($academicTerm, $actor): AcademicTerm {
            $this->lockTenantSchool();
            $lockedTerm = AcademicTerm::query()->lockForUpdate()->findOrFail($academicTerm->getKey());

            if ($lockedTerm->status !== AcademicTerm::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            $academicYear = $this->activeYearForMutation((int) $lockedTerm->academic_year_id);
            $this->validateDateContract(
                $academicYear,
                $lockedTerm->start_date->toDateString(),
                $lockedTerm->end_date->toDateString(),
            );
            $this->validateNoOverlap(
                $academicYear,
                $lockedTerm->start_date->toDateString(),
                $lockedTerm->end_date->toDateString(),
                (int) $lockedTerm->id,
            );

            $oldValues = $this->auditValues($lockedTerm);
            $lockedTerm->forceFill(['status' => AcademicTerm::STATUS_ACTIVE])->save();
            $this->logChangedMutation($lockedTerm, $actor, 'activated', $oldValues);

            return $lockedTerm;
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
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function activeYearForMutation(int $academicYearId): AcademicYear
    {
        $academicYear = AcademicYear::query()->lockForUpdate()->findOrFail($academicYearId);

        if ($academicYear->status !== AcademicYear::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'Select an active academic year.',
            ]);
        }

        return $academicYear;
    }

    private function validateDateContract(AcademicYear $academicYear, string $startDate, string $endDate): void
    {
        $start = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        if ($start->lt($academicYear->start_date) || $end->gt($academicYear->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'Term dates must remain inside the selected academic year.',
            ]);
        }
    }

    private function validateNoOverlap(
        AcademicYear $academicYear,
        string $startDate,
        string $endDate,
        ?int $exceptId = null,
    ): void {
        $start = CarbonImmutable::parse($startDate)->toDateString();
        $end = CarbonImmutable::parse($endDate)->toDateString();

        $overlaps = AcademicTerm::query()
            ->where('academic_year_id', $academicYear->id)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'Academic term dates cannot overlap another term in this academic year.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(AcademicTerm $academicTerm, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($academicTerm);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $academicTerm,
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
        AcademicTerm $academicTerm,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity(
            $actor,
            'academic_structure',
            $action,
            $academicTerm,
            'Academic term '.$action.'.',
        );
        $this->securityLogs->audit($actor, $academicTerm, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(AcademicTerm $academicTerm): array
    {
        return [
            'academic_year_id' => (int) $academicTerm->academic_year_id,
            'name' => $academicTerm->name,
            'term_order' => (int) $academicTerm->term_order,
            'start_date' => $academicTerm->start_date->toDateString(),
            'end_date' => $academicTerm->end_date->toDateString(),
            'status' => $academicTerm->status,
        ];
    }
}
