<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(private readonly SecurityLogService $securityLogs) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $request): void {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => null,
            ])->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();

            $this->securityLogs->activity(
                $user,
                'authentication',
                'password_changed',
                $user,
                'User changed their password with current-password confirmation.',
            );
            $this->securityLogs->audit(
                $user,
                $user,
                'updated',
                ['password_changed' => false],
                ['password_changed' => true],
            );
        });

        return back()->with('status', 'password-updated');
    }
}
