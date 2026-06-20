<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\PasswordSecurityService;
use App\Services\SecurityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PasswordSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_rolls_back_when_security_log_persistence_fails(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OriginalPassword123'),
            'remember_token' => 'original-remember-token',
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $securityLogs = Mockery::mock(SecurityLogService::class);
        $securityLogs->shouldReceive('activity')
            ->once()
            ->andThrow(new RuntimeException('Security log persistence failed.'));
        $service = new PasswordSecurityService($securityLogs);

        try {
            $service->updateAuthenticated($user, 'ChangedPassword123', 'current-session');
            $this->fail('The password workflow should fail when its security log cannot be persisted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Security log persistence failed.', $exception->getMessage());
        }

        $user->refresh();
        $this->assertTrue(Hash::check('OriginalPassword123', $user->password));
        $this->assertSame('original-remember-token', $user->remember_token);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session', 'user_id' => $user->id]);
    }
}
