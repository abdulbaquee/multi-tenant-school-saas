<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function listFor(User $actor): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', User::class));

        $query = User::query()->with(['role', 'school'])->orderBy('name');

        if (! $actor->isSuperAdmin()) {
            $query->where('school_id', $actor->school_id);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRolesFor(User $actor): Collection
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->canManageUsers());

        return Role::query()
            ->whereIn('code', Role::assignableCodesFor($actor))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, School>
     */
    public function availableSchoolsFor(User $actor, ?User $subject = null): Collection
    {
        $this->authorizeActorContext($actor);

        if (! $actor->isSuperAdmin()) {
            return collect();
        }

        return School::query()
            ->where(function ($query) use ($subject): void {
                $query->where('status', School::STATUS_ACTIVE);

                if ($subject?->school_id) {
                    $query->orWhere('id', $subject->school_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function create(array $data, User $actor): User
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', User::class));

        $verificationRequested = array_key_exists('email_verified', $data);

        if ($verificationRequested && ! $actor->canVerifyManagedUserEmails()) {
            throw new AuthorizationException('You cannot verify managed user emails.');
        }

        $markEmailVerified = (bool) ($data['email_verified'] ?? false);
        unset($data['email_verified']);

        $data = $this->applyRoleAndSchoolRules($data, $actor);

        $data['password'] = Hash::make($data['password']);
        $data['status'] = User::STATUS_ACTIVE;

        return DB::transaction(function () use ($data, $markEmailVerified, $actor): User {
            $user = User::create($data);

            if ($markEmailVerified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->securityLogs->activity(
                $actor,
                'user_management',
                'created',
                $user,
                'User account created.',
            );
            $this->securityLogs->audit(
                $actor,
                $user,
                'created',
                newValues: $this->auditValues($user),
            );

            return $user;
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $user));

        $emailChanged = strtolower($user->email) !== strtolower((string) $data['email']);
        $passwordReset = filled($data['password'] ?? null);
        $verificationRequested = array_key_exists('email_verified', $data);
        $canManageVerification = $actor->canVerifyManagedUserEmails()
            && ($actor->isSuperAdmin() || ! $actor->is($user));

        if ($verificationRequested && ! $canManageVerification) {
            throw new AuthorizationException('You cannot verify this email address.');
        }

        if ($passwordReset && $actor->is($user)) {
            throw new AuthorizationException('Use your profile to change your own password.');
        }

        $oldValues = $this->auditValues($user);

        $markEmailVerified = (bool) ($data['email_verified'] ?? false);
        unset($data['email_verified']);

        $data = $this->applyRoleAndSchoolRules($data, $actor, $user);
        unset($data['status']);

        $roleChanged = (int) $user->role_id !== (int) $data['role_id'];
        $schoolOwnershipChanged = $this->normalizeSchoolId($oldValues['school_id'])
            !== $this->normalizeSchoolId($data['school_id'] ?? null);

        if ($roleChanged && ! $actor->hasPermission('roles.assign')) {
            throw new AuthorizationException('You cannot assign user roles.');
        }

        if ($passwordReset) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return DB::transaction(function () use (
            $user,
            $data,
            $verificationRequested,
            $markEmailVerified,
            $emailChanged,
            $passwordReset,
            $roleChanged,
            $schoolOwnershipChanged,
            $oldValues,
            $actor,
        ): User {
            $user->fill($data);

            if ($verificationRequested) {
                $user->forceFill([
                    'email_verified_at' => $markEmailVerified
                        ? ($emailChanged || ! $user->email_verified_at ? now() : $user->email_verified_at)
                        : null,
                ]);
            } elseif ($emailChanged) {
                $user->forceFill(['email_verified_at' => null]);
            }

            if ($passwordReset || $roleChanged || $schoolOwnershipChanged) {
                $user->forceFill(['remember_token' => null]);
            }

            $user->save();

            if ($passwordReset || $roleChanged || $schoolOwnershipChanged) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $newValues = $this->auditValues($user);

            if ($passwordReset) {
                $oldValues['password_changed'] = false;
                $newValues['password_changed'] = true;
            }

            $activityEvents = [];

            if ($passwordReset) {
                $activityEvents['password_reset'] = 'User password reset by an administrator.';
            }

            if ($roleChanged) {
                $activityEvents['role_assigned'] = 'User role assignment changed.';
            }

            if ($activityEvents === []) {
                $activityEvents['updated'] = 'User account updated.';
            }

            if ($schoolOwnershipChanged) {
                foreach ($activityEvents as $action => $description) {
                    $this->securityLogs->platformActivity(
                        $actor,
                        'user_management',
                        $action,
                        $user,
                        $description,
                    );
                }

                $this->securityLogs->platformAudit($actor, $user, 'updated', $oldValues, $newValues);
            } else {
                foreach ($activityEvents as $action => $description) {
                    $this->securityLogs->activity(
                        $actor,
                        'user_management',
                        $action,
                        $user,
                        $description,
                    );
                }

                $this->securityLogs->audit($actor, $user, 'updated', $oldValues, $newValues);
            }

            return $user;
        });
    }

    public function activate(User $user, User $actor): User
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('activate', $user));

        return DB::transaction(function () use ($user, $actor): User {
            $oldValues = ['status' => $user->status];
            $user->status = User::STATUS_ACTIVE;
            $user->save();

            $this->securityLogs->activity(
                $actor,
                'user_management',
                'activated',
                $user,
                'User account activated.',
            );
            $this->securityLogs->audit(
                $actor,
                $user,
                'status_changed',
                $oldValues,
                ['status' => $user->status],
            );

            return $user;
        });
    }

    public function deactivate(User $user, User $actor): User
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('deactivate', $user));

        DB::transaction(function () use ($user, $actor): void {
            $oldValues = ['status' => $user->status];
            $user->forceFill([
                'status' => User::STATUS_INACTIVE,
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            $this->securityLogs->activity(
                $actor,
                'user_management',
                'deactivated',
                $user,
                'User account deactivated.',
            );
            $this->securityLogs->audit(
                $actor,
                $user,
                'status_changed',
                $oldValues,
                ['status' => $user->status],
            );
        });

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyRoleAndSchoolRules(array $data, User $actor, ?User $subject = null): array
    {
        $role = Role::query()->findOrFail($data['role_id']);

        if (! in_array($role->code, Role::assignableCodesFor($actor), true)) {
            throw new AuthorizationException('You cannot assign this role.');
        }

        if ($subject && $actor->is($subject) && (int) $role->id !== (int) $subject->role_id) {
            throw ValidationException::withMessages([
                'role_id' => 'You cannot change your own role.',
            ]);
        }

        if (! $actor->isSuperAdmin()) {
            if (! $actor->school_id) {
                throw ValidationException::withMessages([
                    'school_id' => 'A School Admin must belong to a school.',
                ]);
            }

            if ($subject && (int) $subject->school_id !== (int) $actor->school_id) {
                throw new AuthorizationException('You cannot manage users from another school.');
            }

            $data['school_id'] = $actor->school_id;

            return $data;
        }

        if ($role->code === Role::SUPER_ADMIN) {
            if (filled($data['school_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'school_id' => 'Super Admin users cannot belong to a school.',
                ]);
            }

            $data['school_id'] = null;

            return $data;
        }

        $school = filled($data['school_id'] ?? null)
            ? School::query()->find($data['school_id'])
            : null;

        $isExistingAssignment = $subject
            && $school
            && (int) $subject->school_id === (int) $school->id;

        if (! $school || ($school->status !== School::STATUS_ACTIVE && ! $isExistingAssignment)) {
            throw ValidationException::withMessages([
                'school_id' => 'An active school is required for this role.',
            ]);
        }

        return $data;
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function authorizeActorContext(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $this->tenantContext->isTenant()
                && filled($actor->school_id)
                && (int) $this->tenantContext->schoolId() === (int) $actor->school_id;

        $this->authorize($matchesContext);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(User $user): array
    {
        return $user->only([
            'school_id',
            'role_id',
            'name',
            'email',
            'phone',
            'status',
            'email_verified_at',
        ]);
    }

    private function normalizeSchoolId(mixed $schoolId): ?int
    {
        return filled($schoolId) ? (int) $schoolId : null;
    }
}
