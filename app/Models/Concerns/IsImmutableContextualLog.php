<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait IsImmutableContextualLog
{
    public static function bootIsImmutableContextualLog(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if ($context->isUnresolved()) {
                throw TenantContextException::resolvedContextRequired($model::class);
            }

            if ($context->isTenant()) {
                $model->setAttribute('school_id', $context->tenantId());
            }
        });

        static::updating(function (Model $model): never {
            throw new LogicException($model::class.' records are immutable.');
        });

        static::deleting(function (Model $model): never {
            throw new LogicException($model::class.' records are immutable.');
        });
    }
}
