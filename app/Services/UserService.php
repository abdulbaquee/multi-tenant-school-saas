<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function listFor(User $actor): LengthAwarePaginator
    {
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

        $user = User::create($data);

        if ($markEmailVerified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    public function update(User $user, array $data, User $actor): User
    {
        $this->authorize($actor->can('update', $user));

        $emailChanged = strtolower($user->email) !== strtolower((string) $data['email']);
        $verificationRequested = array_key_exists('email_verified', $data);
        $canManageVerification = $actor->canVerifyManagedUserEmails()
            && ($actor->isSuperAdmin() || ! $actor->is($user));

        if ($verificationRequested && ! $canManageVerification) {
            throw new AuthorizationException('You cannot verify this email address.');
        }

        $markEmailVerified = (bool) ($data['email_verified'] ?? false);
        unset($data['email_verified']);

        $data = $this->applyRoleAndSchoolRules($data, $actor, $user);
        unset($data['status']);

        if (array_key_exists('password', $data) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

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

        $user->save();

        return $user;
    }

    public function activate(User $user, User $actor): User
    {
        $this->authorize($actor->can('activate', $user));

        $user->status = User::STATUS_ACTIVE;
        $user->save();

        return $user;
    }

    public function deactivate(User $user, User $actor): User
    {
        $this->authorize($actor->can('deactivate', $user));

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'status' => User::STATUS_INACTIVE,
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();
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
}
