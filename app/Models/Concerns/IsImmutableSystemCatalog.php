<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use LogicException;

trait IsImmutableSystemCatalog
{
    public static function bootIsImmutableSystemCatalog(): void
    {
        static::creating(fn (Model $model): never => throw new LogicException($model::class.' records are system-managed.'));
        static::updating(fn (Model $model): never => throw new LogicException($model::class.' records are system-managed.'));
        static::deleting(fn (Model $model): never => throw new LogicException($model::class.' records are system-managed.'));
    }
}
