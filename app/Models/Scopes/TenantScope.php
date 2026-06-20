<?php

namespace App\Models\Scopes;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isTenant()) {
            $builder->where(
                $model->qualifyColumn('school_id'),
                $context->tenantId(),
            );

            return;
        }

        if ($context->isUnresolved()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
