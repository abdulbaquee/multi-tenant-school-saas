<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder): void {
            $schoolId = TenantContext::id();

            if ($schoolId !== null) {
                $builder->where($builder->qualifyColumn('school_id'), $schoolId);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->school_id) && TenantContext::id() !== null) {
                $model->school_id = TenantContext::id();
            }
        });
    }
}
