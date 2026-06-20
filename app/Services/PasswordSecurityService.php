<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordSecurityService
{
    public function __construct(private readonly SecurityLogService $securityLogs) {}

    public function updateAuthenticated(User $user, string $password, string $currentSessionId): User
    {
        return DB::transaction(function () use ($user, $password, $currentSessionId): User {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => null,
            ])->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
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

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function resetWithToken(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => null,
                    ])->save();

                    DB::table('sessions')->where('user_id', $user->id)->delete();

                    $this->securityLogs->authenticationActivity(
                        $user,
                        'password_reset',
                        description: 'User reset their password using a verified reset token.',
                    );
                    $this->securityLogs->authenticationAudit(
                        $user,
                        $user,
                        'updated',
                        ['password_changed' => false],
                        ['password_changed' => true],
                    );
                });

                event(new PasswordReset($user));
            },
        );
    }
}
