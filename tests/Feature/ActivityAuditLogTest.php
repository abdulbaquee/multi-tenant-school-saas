<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SecurityLogService;
use App\Support\PrivacySafeLogValues;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_from_log_review_routes(): void
    {
        $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }

    public function test_teacher_and_accountant_cannot_open_log_review_screens(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher-logs@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant-logs@example.com');

        $this->actingAs($teacher)->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_super_admin_reviews_platform_wide_activity_and_audit_logs(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $adminOne = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin-one-logs@example.com');
        $adminTwo = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolTwo, 'admin-two-logs@example.com');
        $logs = app(SecurityLogService::class);

        $this->tenant($schoolOne, fn () => $logs->activity($adminOne, 'students', 'created', $adminOne, 'School one activity.'));
        $this->tenant($schoolTwo, fn () => $logs->activity($adminTwo, 'fees', 'updated', $adminTwo, 'School two activity.'));

        $response = $this->actingAs($this->superAdmin())->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('School one activity.')
            ->assertSee('School two activity.')
            ->assertSee('School One')
            ->assertSee('School Two');
    }

    public function test_school_admin_sees_only_own_school_logs(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $adminOne = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin-one-scope@example.com');
        $adminTwo = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolTwo, 'admin-two-scope@example.com');
        $logs = app(SecurityLogService::class);

        $activityOne = $this->tenant($schoolOne, fn () => $logs->activity($adminOne, 'students', 'created', $adminOne, 'Visible to school one.'));
        $this->tenant($schoolTwo, fn () => $logs->activity($adminTwo, 'students', 'created', $adminTwo, 'Hidden from school one.'));

        $response = $this->actingAs($adminOne)->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('Visible to school one.')
            ->assertDontSee('Hidden from school one.')
            ->assertDontSee('School Two');

        $this->actingAs($adminOne)->get(route('activity-logs.show', $activityOne))->assertOk();

        $foreignLog = $this->tenant($schoolTwo, fn () => ActivityLog::query()->firstOrFail());
        $this->actingAs($adminOne)->get(route('activity-logs.show', $foreignLog))->assertNotFound();
    }

    public function test_audit_detail_renders_privacy_safe_values_only(): void
    {
        $school = $this->school('Privacy');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-privacy@example.com');
        $logs = app(SecurityLogService::class);

        $audit = $this->tenant($school, fn () => $logs->audit(
            $admin,
            $admin,
            'updated',
            [
                'name' => 'Before',
                'guardian_phone' => '9999999999',
                'password' => 'OldPassword123',
            ],
            [
                'name' => 'After',
                'guardian_email' => 'guardian@example.com',
                'address' => 'Hidden Street',
                'safe_field' => 'retained',
            ],
        ));

        $sanitizedOld = app(AuditLogService::class)->sanitizedOldValues($audit);
        $sanitizedNew = app(AuditLogService::class)->sanitizedNewValues($audit);

        $this->assertSame(['name' => 'Before'], $sanitizedOld);
        $this->assertSame(['name' => 'After', 'safe_field' => 'retained'], $sanitizedNew);

        $response = $this->actingAs($admin)->get(route('audit-logs.show', $audit));

        $response->assertOk()
            ->assertSee('safe_field')
            ->assertSee('retained')
            ->assertDontSee('guardian@example.com')
            ->assertDontSee('Hidden Street')
            ->assertDontSee('9999999999')
            ->assertDontSee('OldPassword123');
    }

    public function test_log_exports_are_available_to_authorized_reviewers(): void
    {
        $school = $this->school('Export');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-export@example.com');
        $logs = app(SecurityLogService::class);

        $this->tenant($school, fn () => $logs->activity($admin, 'reports', 'exported', $admin, 'Exported student report.'));

        $response = $this->actingAs($admin)->get(route('activity-logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean() ?: '';

        $this->assertStringContainsString('Exported student report.', $content);
    }

    public function test_log_filters_reject_tenant_override_fields(): void
    {
        $school = $this->school('Filter');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-filter@example.com');

        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['school_id' => 99]))
            ->assertSessionHasErrors('school_id');
    }

    public function test_privacy_safe_log_values_strip_nested_sensitive_keys(): void
    {
        $sanitized = PrivacySafeLogValues::sanitize([
            'name' => 'Student',
            'nested' => [
                'guardian_name' => 'Hidden',
                'status' => 'active',
            ],
        ]);

        $this->assertSame([
            'name' => 'Student',
            'nested' => [
                'status' => 'active',
            ],
        ], $sanitized);
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'LOG-'.$key,
            'email' => 'logs-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => (int) Role::query()->where('code', $roleCode)->value('id'),
            'email' => $email,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant((int) $school->id, $callback);
    }
}
