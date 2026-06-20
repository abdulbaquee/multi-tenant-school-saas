<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->users->listFor($request->user());

        return view('users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $roles = $this->users->assignableRolesFor($request->user());
        $schools = $this->users->availableSchoolsFor($request->user());

        return view('users.create', compact('roles', 'schools'));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->users->create($request->validated(), $request->user());

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['role', 'school']);

        return view('users.show', compact('user'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $roles = $this->users->assignableRolesFor($request->user());
        $schools = $this->users->availableSchoolsFor($request->user(), $user);

        return view('users.edit', compact('user', 'roles', 'schools'));
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->users->update($user, $request->validated(), $request->user());

        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('activate', $user);

        $this->users->activate($user, $request->user());

        return redirect()->route('users.index')->with('status', 'User activated successfully.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $this->users->deactivate($user, $request->user());

        return redirect()->route('users.index')->with('status', 'User deactivated successfully.');
    }
}
