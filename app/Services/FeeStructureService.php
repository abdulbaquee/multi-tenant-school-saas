<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeStructureService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, FeeStructure>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', FeeStructure::class));

        return FeeStructure::query()
            ->with(['feeCategory', 'academicYear', 'schoolClass'])
            ->withCount(['studentFees' => fn ($query) => $query->withTrashed()])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->whereHas('feeCategory', fn ($query) => $query->where('name', 'like', $search));
            })
            ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->where('status', FeeStructure::STATUS_ACTIVE))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->where('status', FeeStructure::STATUS_INACTIVE))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(FeeStructure $feeStructure, User $actor): FeeStructure
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $feeStructure));

        return $feeStructure->load([
            'feeCategory',
            'academicYear',
            'schoolClass',
        ])->loadCount(['studentFees' => fn ($query) => $query->withTrashed()]);
    }

    /**
     * @return array{
     *     categories: Collection<int, FeeCategory>,
     *     academicYears: Collection<int, AcademicYear>,
     *     classes: Collection<int, SchoolClass>
     * }
     */
    public function formOptions(User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', FeeStructure::class));

        return [
            'categories' => FeeCategory::query()
                ->where('status', FeeCategory::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::query()
                ->where('status', AcademicYear::STATUS_ACTIVE)
                ->where('is_current', true)
                ->orderByDesc('start_date')
                ->get(),
            'classes' => SchoolClass::query()
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * @param  array{
     *     fee_category_id: int,
     *     academic_year_id: int,
     *     class_id: int,
     *     amount: numeric-string|float|int,
     *     due_date?: string|null,
     *     frequency: string
     * }  $data
     */
    public function create(array $data, User $actor): FeeStructure
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', FeeStructure::class));

        return DB::transaction(function () use ($data, $actor): FeeStructure {
            $this->lockTenantSchool();
            $this->assertSetupReferencesAreValid($data);
            $feeStructure = FeeStructure::create([
                ...$data,
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);
            $this->logMutation($feeStructure, $actor, 'created', [], $this->auditValues($feeStructure));

            return $feeStructure;
        });
    }

    /**
     * @param  array{
     *     amount: numeric-string|float|int,
     *     due_date?: string|null,
     *     frequency: string
     * }  $data
     */
    public function update(FeeStructure $feeStructure, array $data, User $actor): FeeStructure
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $feeStructure));

        return DB::transaction(function () use ($feeStructure, $data, $actor): FeeStructure {
            $this->lockTenantSchool();
            $lockedStructure = FeeStructure::query()->lockForUpdate()->findOrFail($feeStructure->getKey());
            $oldValues = $this->auditValues($lockedStructure);
            $lockedStructure->fill($data)->save();
            $this->logChangedMutation($lockedStructure, $actor, 'updated', $oldValues);

            return $lockedStructure;
        });
    }

    public function activate(FeeStructure $feeStructure, User $actor): FeeStructure
    {
        return $this->changeStatus(
            $feeStructure,
            $actor,
            FeeStructure::STATUS_INACTIVE,
            FeeStructure::STATUS_ACTIVE,
            'activate',
            'activated',
        );
    }

    public function deactivate(FeeStructure $feeStructure, User $actor): FeeStructure
    {
        return $this->changeStatus(
            $feeStructure,
            $actor,
            FeeStructure::STATUS_ACTIVE,
            FeeStructure::STATUS_INACTIVE,
            'deactivate',
            'deactivated',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertSetupReferencesAreValid(array $data): void
    {
        $category = FeeCategory::query()
            ->whereKey($data['fee_category_id'])
            ->where('status', FeeCategory::STATUS_ACTIVE)
            ->first();

        if (! $category instanceof FeeCategory) {
            throw ValidationException::withMessages([
                'fee_category_id' => 'Select an active Fee Category in the current school.',
            ]);
        }

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
    }

    private function changeStatus(
        FeeStructure $feeStructure,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): FeeStructure {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $feeStructure));

        return DB::transaction(function () use ($feeStructure, $actor, $from, $to, $action): FeeStructure {
            $this->lockTenantSchool();
            $lockedStructure = FeeStructure::query()->lockForUpdate()->findOrFail($feeStructure->getKey());

            if ($lockedStructure->status !== $from) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedStructure);
            $lockedStructure->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedStructure, $actor, $action, $oldValues);

            return $lockedStructure;
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
    private function auditValues(FeeStructure $feeStructure): array
    {
        return [
            'fee_category_id' => $feeStructure->fee_category_id,
            'academic_year_id' => $feeStructure->academic_year_id,
            'class_id' => $feeStructure->class_id,
            'amount' => $feeStructure->amount,
            'due_date' => optional($feeStructure->due_date)?->toDateString(),
            'frequency' => $feeStructure->frequency,
            'status' => $feeStructure->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(FeeStructure $feeStructure, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($feeStructure);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $feeStructure,
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
        FeeStructure $feeStructure,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'fee_management', $action, $feeStructure, 'Fee Structure '.$action.'.');
        $this->securityLogs->audit($actor, $feeStructure, $action, $oldValues, $newValues);
    }
}
