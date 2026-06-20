<?php

namespace App\Models;

use App\Models\Concerns\IsImmutableSystemCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'description', 'is_system'])]
class Role extends Model
{
    use IsImmutableSystemCatalog;

    public const SUPER_ADMIN = 'super_admin';

    public const SCHOOL_ADMIN = 'school_admin';

    public const TEACHER = 'teacher';

    public const ACCOUNTANT = 'accountant';

    /**
     * @return list<string>
     */
    public static function assignableCodesFor(User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return [self::SUPER_ADMIN, self::SCHOOL_ADMIN, self::TEACHER, self::ACCOUNTANT];
        }

        return [self::SCHOOL_ADMIN, self::TEACHER, self::ACCOUNTANT];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }
}
