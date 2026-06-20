<?php

namespace App\Tenancy;

use RuntimeException;

final class TenantContextException extends RuntimeException
{
    public static function tenantRequired(string $model): self
    {
        return new self("Tenant context is required to create {$model}.");
    }

    public static function resolvedContextRequired(string $model): self
    {
        return new self("Resolved Tenant or Platform context is required to create {$model}.");
    }

    public static function immutableOwnership(string $model): self
    {
        return new self("Tenant ownership cannot be changed for {$model}.");
    }
}
