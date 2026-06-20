<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Models\User;
use App\Services\PasswordSecurityService;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordSecurityService $passwords) {}

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->passwords->updateAuthenticated(
            $user,
            $request->validated('password'),
            $request->session()->getId(),
        );

        return back()->with('status', 'password-updated');
    }
}
