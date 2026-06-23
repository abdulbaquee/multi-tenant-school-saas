<?php

namespace App\Services;

use App\Models\FeeCategory;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeeCategoryService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, FeeCategory>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', FeeCategory::class));

        return FeeCategory::query()
            ->withCount(['feeStructures' => fn ($query) => $query->withTrashed()])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->where('status', FeeCategory::STATUS_ACTIVE))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->where('status', FeeCategory::STATUS_INACTIVE))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(FeeCategory $feeCategory, User $actor): FeeCategory
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $feeCategory));

        return $feeCategory->loadCount(['feeStructures' => fn ($query) => $query->withTrashed()]);
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function create(array $data, User $actor): FeeCategory
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', FeeCategory::class));

        return DB::transaction(function () use ($data, $actor): FeeCategory {
            $this->lockTenantSchool();
            $feeCategory = FeeCategory::create([
                ...$data,
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
            $this->logMutation($feeCategory, $actor, 'created', [], $this->auditValues($feeCategory));

            return $feeCategory;
        });
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function update(FeeCategory $feeCategory, array $data, User $actor): FeeCategory
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $feeCategory));

        return DB::transaction(function () use ($feeCategory, $data, $actor): FeeCategory {
            $this->lockTenantSchool();
            $lockedCategory = FeeCategory::query()->lockForUpdate()->findOrFail($feeCategory->getKey());
            $oldValues = $this->auditValues($lockedCategory);
            $lockedCategory->fill($data)->save();
            $this->logChangedMutation($lockedCategory, $actor, 'updated', $oldValues);

            return $lockedCategory;
        });
    }

    public function activate(FeeCategory $feeCategory, User $actor): FeeCategory
    {
        return $this->changeStatus(
            $feeCategory,
            $actor,
            FeeCategory::STATUS_INACTIVE,
            FeeCategory::STATUS_ACTIVE,
            'activate',
            'activated',
        );
    }

    public function deactivate(FeeCategory $feeCategory, User $actor): FeeCategory
    {
        return $this->changeStatus(
            $feeCategory,
            $actor,
            FeeCategory::STATUS_ACTIVE,
            FeeCategory::STATUS_INACTIVE,
            'deactivate',
            'deactivated',
        );
    }

    private function changeStatus(
        FeeCategory $feeCategory,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): FeeCategory {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $feeCategory));

        return DB::transaction(function () use ($feeCategory, $actor, $from, $to, $action): FeeCategory {
            $this->lockTenantSchool();
            $lockedCategory = FeeCategory::query()->lockForUpdate()->findOrFail($feeCategory->getKey());

            if ($lockedCategory->status !== $from) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedCategory);
            $lockedCategory->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedCategory, $actor, $action, $oldValues);

            return $lockedCategory;
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
    private function auditValues(FeeCategory $feeCategory): array
    {
        return [
            'name' => $feeCategory->name,
            'description' => $feeCategory->description,
            'status' => $feeCategory->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(FeeCategory $feeCategory, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($feeCategory);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $feeCategory,
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
        FeeCategory $feeCategory,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'fee_management', $action, $feeCategory, 'Fee Category '.$action.'.');
        $this->securityLogs->audit($actor, $feeCategory, $action, $oldValues, $newValues);
    }
}
