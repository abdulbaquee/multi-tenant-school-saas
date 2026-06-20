<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolePermissionService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function listFor(User $actor): Collection
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Role::class));

        $query = Role::query()
            ->withCount('permissions')
            ->orderByRaw("CASE code WHEN 'super_admin' THEN 1 WHEN 'school_admin' THEN 2 WHEN 'teacher' THEN 3 WHEN 'accountant' THEN 4 ELSE 5 END");

        if (! $actor->isSuperAdmin()) {
            $query->whereIn('code', $this->schoolRoleCodes());
        }

        return $query->get();
    }

    public function detailsFor(Role $role, User $actor): Role
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $role));

        return $role->load(['permissions' => fn ($query) => $query
            ->orderBy('module')
            ->orderBy('name')]);
    }

    /**
     * @return array{
     *     role: Role,
     *     permissionsByModule: Collection<string, Collection<int, Permission>>,
     *     selectedCodes: list<string>,
     *     essentialCodes: list<string>,
     *     fingerprint: string
     * }
     */
    public function editDataFor(Role $role, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $role));

        $maximumCodes = $this->configuredCodes('maximum_mappings', $role);
        $permissions = Permission::query()
            ->whereIn('code', $maximumCodes)
            ->orderBy('module')
            ->orderBy('name')
            ->get();
        $selectedCodes = $this->currentCodes($role);

        return [
            'role' => $role,
            'permissionsByModule' => $permissions->groupBy('module'),
            'selectedCodes' => $selectedCodes,
            'essentialCodes' => $this->configuredCodes('essential_permissions', $role),
            'fingerprint' => $this->fingerprintForCodes($selectedCodes),
        ];
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function updateMapping(
        Role $role,
        array $permissionIds,
        string $fingerprint,
        User $actor,
    ): Role {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $role));

        if (count($permissionIds) !== count(array_unique($permissionIds))) {
            throw ValidationException::withMessages([
                'permission_ids' => 'Duplicate permissions are not allowed.',
            ]);
        }

        return DB::transaction(function () use ($role, $permissionIds, $fingerprint, $actor): Role {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            $currentCodes = $this->currentCodes($lockedRole);

            if (! hash_equals($this->fingerprintForCodes($currentCodes), $fingerprint)) {
                throw ValidationException::withMessages([
                    'mapping_fingerprint' => 'This permission mapping changed after you opened it. Review the latest mapping and try again.',
                ]);
            }

            $submittedPermissions = Permission::query()
                ->whereIn('id', $permissionIds)
                ->get(['id', 'code']);

            if ($submittedPermissions->count() !== count($permissionIds)) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'One or more selected permissions are invalid.',
                ]);
            }

            $submittedCodes = $submittedPermissions->pluck('code')->all();
            $maximumCodes = $this->configuredCodes('maximum_mappings', $lockedRole);
            $outOfBounds = array_values(array_diff($submittedCodes, $maximumCodes));

            if ($outOfBounds !== []) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'One or more selected permissions are not allowed for this role.',
                ]);
            }

            $essentialCodes = $this->configuredCodes('essential_permissions', $lockedRole);
            $missingEssentials = array_values(array_diff($essentialCodes, $submittedCodes));

            if ($missingEssentials !== []) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'Essential permissions cannot be removed from this role.',
                ]);
            }

            $desiredCodes = array_values(array_unique([
                ...$submittedCodes,
                ...$essentialCodes,
            ]));
            sort($desiredCodes);

            $desiredIds = Permission::query()
                ->whereIn('code', $desiredCodes)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $lockedRole->permissions()->sync($desiredIds);

            $this->securityLogs->platformActivity(
                $actor,
                'role_permissions',
                'mapping_updated',
                $lockedRole,
                'Role permission mapping updated.',
            );
            $this->securityLogs->platformAudit(
                $actor,
                $lockedRole,
                'mapping_updated',
                ['permissions' => $currentCodes],
                ['permissions' => $desiredCodes],
            );

            return $lockedRole->load('permissions');
        });
    }

    /**
     * @return list<string>
     */
    public function currentCodes(Role $role): array
    {
        $codes = $role->permissions()->pluck('code')->all();
        sort($codes);

        return array_values($codes);
    }

    /**
     * @param  list<string>  $codes
     */
    public function fingerprintForCodes(array $codes): string
    {
        sort($codes);

        return hash('sha256', implode("\n", $codes));
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->canEstablishTenantContext() && ($actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : filled($actor->school_id)
                && $this->tenantContext->isTenant()
                && (int) $this->tenantContext->schoolId() === (int) $actor->school_id);

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return list<string>
     */
    private function configuredCodes(string $mapping, Role $role): array
    {
        $codes = config("rbac.{$mapping}.{$role->code}", []);

        if (! is_array($codes)) {
            throw new AuthorizationException;
        }

        $codes = array_values(array_filter($codes, 'is_string'));
        sort($codes);

        return $codes;
    }

    /**
     * @return list<string>
     */
    private function schoolRoleCodes(): array
    {
        return [Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT];
    }
}
