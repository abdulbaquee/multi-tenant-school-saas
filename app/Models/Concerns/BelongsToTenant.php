<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if (! $context->isTenant()) {
                throw TenantContextException::tenantRequired($model::class);
            }

            $model->setAttribute('school_id', $context->tenantId());
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('school_id')) {
                throw TenantContextException::immutableOwnership($model::class);
            }
        });
    }
}
