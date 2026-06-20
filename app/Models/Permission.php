<?php

namespace App\Models;

use App\Models\Concerns\IsImmutableSystemCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'code', 'module', 'description'])]
class Permission extends Model
{
    use IsImmutableSystemCatalog;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')->withTimestamps();
    }
}
