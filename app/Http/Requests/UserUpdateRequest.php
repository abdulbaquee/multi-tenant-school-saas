<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');
        $actor = $this->user();
        $assignableRoleCodes = $actor instanceof User ? Role::assignableCodesFor($actor) : [];

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereIn('code', $assignableRoleCodes),
            ],
            'school_id' => $actor?->isSuperAdmin()
                ? [
                    'nullable',
                    'integer',
                    Rule::exists('schools', 'id')
                        ->whereNull('deleted_at')
                        ->where(function ($query) use ($user): void {
                            $query->where('status', School::STATUS_ACTIVE);

                            if ($user->school_id) {
                                $query->orWhere('id', $user->school_id);
                            }
                        }),
                ]
                : ['prohibited'],
            'email_verified' => $actor?->canVerifyManagedUserEmails()
                && ($actor->isSuperAdmin() || ! $actor->is($user))
                    ? ['sometimes', 'boolean']
                    : ['prohibited'],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $actor = $this->user();
                $user = $this->route('user');
                $role = Role::query()->find($this->integer('role_id'));

                if (! $actor instanceof User || ! $user instanceof User || ! $role) {
                    return;
                }

                if ($actor->is($user) && $role->isNot($user->role)) {
                    $validator->errors()->add('role_id', 'You cannot change your own role.');
                }

                if (! $actor->isSuperAdmin()) {
                    return;
                }

                if ($role->code === Role::SUPER_ADMIN && $this->filled('school_id')) {
                    $validator->errors()->add('school_id', 'Super Admin users cannot belong to a school.');
                }

                if ($role->code !== Role::SUPER_ADMIN && ! $this->filled('school_id')) {
                    $validator->errors()->add('school_id', 'A school is required for this role.');
                }
            },
        ];
    }
}
