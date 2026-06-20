<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\SecurityLogService;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SecurityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_activity_and_audit_logs_are_tenant_scoped_and_strip_sensitive_values(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $actorOne = $this->schoolUser($schoolOne, 'admin-one@example.com');
        $actorTwo = $this->schoolUser($schoolTwo, 'admin-two@example.com');
        $context = app(TenantContext::class);
        $logs = app(SecurityLogService::class);

        $context->setTenant($schoolOne->id);
        $logs->activity($actorOne, 'security_test', 'created', $actorOne, 'Tenant one event.');
        $auditOne = $logs->audit(
            $actorOne,
            $actorOne,
            'updated',
            ['name' => 'Before', 'password' => 'OldPassword123'],
            [
                'name' => 'After',
                'password' => 'NewPassword123',
                'nested' => ['reset_token' => 'secret-token', 'safe' => 'retained'],
            ],
        );

        $this->assertArrayNotHasKey('password', $auditOne->old_values);
        $this->assertArrayNotHasKey('password', $auditOne->new_values);
        $this->assertArrayNotHasKey('reset_token', $auditOne->new_values['nested']);
        $this->assertSame('retained', $auditOne->new_values['nested']['safe']);

        $context->setTenant($schoolTwo->id);
        $logs->activity($actorTwo, 'security_test', 'created', $actorTwo, 'Tenant two event.');
        $logs->audit($actorTwo, $actorTwo, 'updated', ['name' => 'Before'], ['name' => 'After']);

        $context->setTenant($schoolOne->id);
        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertSame(1, AuditLog::query()->count());

        $context->setTenant($schoolTwo->id);
        $this->assertSame(1, ActivityLog::query()->count());
        $this->assertSame(1, AuditLog::query()->count());

        $context->setPlatform();
        $this->assertSame(2, ActivityLog::query()->count());
        $this->assertSame(2, AuditLog::query()->count());

        $context->clear();
        $this->assertSame(0, ActivityLog::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_log_models_reject_unresolved_creation_update_and_deletion(): void
    {
        $school = $this->school('One');
        $actor = $this->schoolUser($school, 'admin@example.com');
        $context = app(TenantContext::class);

        try {
            ActivityLog::create([
                'user_id' => $actor->id,
                'module' => 'security_test',
                'action' => 'created',
            ]);
            $this->fail('Unresolved context created an activity log.');
        } catch (TenantContextException) {
            $this->addToAssertionCount(1);
        }

        $context->setTenant($school->id);
        $activity = app(SecurityLogService::class)
            ->activity($actor, 'security_test', 'created', $actor, 'Immutable event.');
        $audit = app(SecurityLogService::class)
            ->audit($actor, $actor, 'updated', ['name' => 'Before'], ['name' => 'After']);

        foreach ([$activity, $audit] as $log) {
            try {
                $log->update(['user_agent' => 'changed']);
                $this->fail('An immutable log was updated.');
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }

            try {
                $log->delete();
                $this->fail('An immutable log was deleted.');
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_platform_activity_derives_school_from_the_subject(): void
    {
        $school = $this->school('One');
        $superAdmin = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
        app(TenantContext::class)->setPlatform();

        $activity = app(SecurityLogService::class)
            ->activity($superAdmin, 'school_management', 'updated', $school, 'School updated.');

        $this->assertSame($school->id, $activity->school_id);
        $this->assertSame($superAdmin->id, $activity->user_id);
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'SEC-'.$key,
            'email' => 'security-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
            'email' => $email,
        ]);
    }
}
