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

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();
        $assignableRoleCodes = $actor instanceof User ? Role::assignableCodesFor($actor) : [];

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique('users', 'email')],
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
                        ->where('status', School::STATUS_ACTIVE),
                ]
                : ['prohibited'],
            'email_verified' => $actor?->canVerifyManagedUserEmails()
                ? ['sometimes', 'boolean']
                : ['prohibited'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
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

                if (! $actor?->isSuperAdmin()) {
                    return;
                }

                $role = Role::query()->find($this->integer('role_id'));

                if (! $role) {
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
