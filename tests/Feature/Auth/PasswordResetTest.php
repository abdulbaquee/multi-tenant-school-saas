<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->forceFill(['remember_token' => 'remember-me'])->save();
        DB::table('sessions')->insert([
            'id' => 'password-reset-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'ResetPassword123',
                'password_confirmation' => 'ResetPassword123',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $user->refresh();
            $this->assertTrue(Hash::check('ResetPassword123', $user->password));
            $this->assertNull($user->remember_token);
            $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $user->id,
                'action' => 'password_reset',
            ]);
            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $user->id,
                'auditable_id' => $user->id,
                'event' => 'updated',
            ]);

            return true;
        });
    }

    public function test_weak_password_is_rejected_during_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'weakpassword',
                'password_confirmation' => 'weakpassword',
            ])->assertSessionHasErrors('password');

            return true;
        });
    }

    public function test_forgot_password_response_does_not_disclose_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $knownResponse = $this->post('/forgot-password', ['email' => $user->email]);
        $unknownResponse = $this->post('/forgot-password', ['email' => 'missing@example.com']);

        $knownResponse->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        $unknownResponse->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        $unknownResponse->assertSessionHasNoErrors();
    }

    public function test_forgot_password_requests_are_rate_limited(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/forgot-password', ['email' => 'missing@example.com'])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'missing@example.com'])->assertTooManyRequests();
    }
}
