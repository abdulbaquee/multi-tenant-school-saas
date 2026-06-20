<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RolePermissionUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('permission_ids')) {
            $this->merge(['permission_ids' => []]);
        }
    }

    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            && ($this->user()?->can('update', $role) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mapping_fingerprint' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }
}
