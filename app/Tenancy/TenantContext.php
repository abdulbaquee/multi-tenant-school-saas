<?php

namespace App\Tenancy;

use InvalidArgumentException;
use LogicException;

final class TenantContext
{
    private TenantContextState $state = TenantContextState::Unresolved;

    private ?int $schoolId = null;

    public function state(): TenantContextState
    {
        return $this->state;
    }

    public function schoolId(): ?int
    {
        return $this->schoolId;
    }

    public function tenantId(): int
    {
        if (! $this->isTenant() || $this->schoolId === null) {
            throw new LogicException('A tenant context is required.');
        }

        return $this->schoolId;
    }

    public function setTenant(int $schoolId): void
    {
        if ($schoolId < 1) {
            $this->clear();

            throw new InvalidArgumentException('The tenant school id must be positive.');
        }

        $this->state = TenantContextState::Tenant;
        $this->schoolId = $schoolId;
    }

    public function setPlatform(): void
    {
        $this->state = TenantContextState::Platform;
        $this->schoolId = null;
    }

    public function clear(): void
    {
        $this->state = TenantContextState::Unresolved;
        $this->schoolId = null;
    }

    public function isUnresolved(): bool
    {
        return $this->state === TenantContextState::Unresolved;
    }

    public function isTenant(): bool
    {
        return $this->state === TenantContextState::Tenant;
    }

    public function isPlatform(): bool
    {
        return $this->state === TenantContextState::Platform;
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function runAsTenant(int $schoolId, callable $callback): mixed
    {
        $previousState = $this->state;
        $previousSchoolId = $this->schoolId;

        $this->setTenant($schoolId);

        try {
            return $callback();
        } finally {
            $this->restore($previousState, $previousSchoolId);
        }
    }

    private function restore(TenantContextState $state, ?int $schoolId): void
    {
        if ($state === TenantContextState::Tenant) {
            $this->setTenant($schoolId
                ?? throw new LogicException('A tenant state must have a school id.'));

            return;
        }

        if ($state === TenantContextState::Platform) {
            $this->setPlatform();

            return;
        }

        $this->clear();
    }
}
