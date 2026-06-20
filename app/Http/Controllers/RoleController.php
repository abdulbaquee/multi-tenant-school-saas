<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolePermissionUpdateRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly RolePermissionService $roles) {}

    public function index(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('roles.index', [
            'roles' => $this->roles->listFor($actor),
        ]);
    }

    public function show(Request $request, Role $role): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $role = $this->roles->detailsFor($role, $actor);

        return view('roles.show', [
            'role' => $role,
            'permissionsByModule' => $role->permissions->groupBy('module'),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('roles.edit', $this->roles->editDataFor($role, $actor));
    }

    public function update(RolePermissionUpdateRequest $request, Role $role): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $this->roles->updateMapping(
            $role,
            array_map('intval', $validated['permission_ids']),
            $validated['mapping_fingerprint'],
            $actor,
        );

        return redirect()
            ->route('roles.show', $role)
            ->with('status', 'Role permission mapping updated successfully.');
    }
}
