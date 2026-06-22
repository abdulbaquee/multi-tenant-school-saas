<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['school_id', 'role_id', 'name', 'email', 'password', 'phone', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function markedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'marked_by');
    }

    public function hasRoleCode(string $code): bool
    {
        return $this->role?->code === $code;
    }

    public function hasPermission(string $code): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->role()
            ->whereHas('permissions', fn ($query) => $query->where('code', $code))
            ->exists();
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('users.view')
            && ($this->isSuperAdmin()
                || ($this->hasRoleCode(Role::SCHOOL_ADMIN) && filled($this->school_id)));
    }

    public function canVerifyManagedUserEmails(): bool
    {
        return $this->hasPermission('users.update')
            && ($this->isSuperAdmin()
            || ($this->hasRoleCode(Role::SCHOOL_ADMIN)
                && filled($this->school_id)
                && $this->hasVerifiedEmail()));
    }

    public function canViewDashboard(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (! $this->hasPermission('dashboard.view')) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return filled($this->school_id)
            && in_array($this->role?->code, [Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRoleCode(Role::SUPER_ADMIN) && is_null($this->school_id);
    }

    public function canEstablishTenantContext(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! filled($this->school_id)
            || ! in_array($this->role?->code, [Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT], true)) {
            return false;
        }

        return $this->school()
            ->where('status', School::STATUS_ACTIVE)
            ->exists();
    }
}
