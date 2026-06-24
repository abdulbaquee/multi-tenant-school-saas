<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed();
    }

    public function test_guests_are_redirected_from_backup_routes(): void
    {
        $this->get(route('backups.index'))->assertRedirect(route('login'));
    }

    public function test_school_roles_are_denied_backup_management(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-backup@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher-backup@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant-backup@example.com');

        foreach ([$admin, $teacher, $accountant] as $actor) {
            $this->actingAs($actor)->get(route('backups.index'))->assertForbidden();
            $this->actingAs($actor)->post(route('backups.store'))->assertForbidden();
        }
    }

    public function test_super_admin_can_create_download_and_soft_delete_platform_backup(): void
    {
        $superAdmin = $this->superAdmin();

        $createResponse = $this->actingAs($superAdmin)->post(route('backups.store'));

        $backupLog = BackupLog::query()->firstOrFail();
        $createResponse->assertRedirect(route('backups.show', $backupLog));

        $this->assertSame(BackupLog::STATUS_COMPLETED, $backupLog->status);
        $this->assertNotNull($backupLog->file_path);
        Storage::disk('local')->assertExists($backupLog->file_path);

        $this->actingAs($superAdmin)
            ->get(route('backups.show', $backupLog))
            ->assertOk()
            ->assertSee('Backup Details')
            ->assertSee('Completed');

        $downloadResponse = $this->actingAs($superAdmin)->get(route('backups.download', $backupLog));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('application/zip', (string) $downloadResponse->headers->get('content-type'));

        $deleteResponse = $this->actingAs($superAdmin)->delete(route('backups.destroy', $backupLog));
        $deleteResponse->assertRedirect(route('backups.show', $backupLog));

        $backupLog->refresh();
        $this->assertSame(BackupLog::STATUS_DELETED, $backupLog->status);
        $this->assertNotNull($backupLog->id);
        Storage::disk('local')->assertMissing($backupLog->file_path);

        $this->actingAs($superAdmin)
            ->get(route('backups.download', $backupLog))
            ->assertForbidden();
    }

    public function test_backup_actions_write_privacy_safe_activity_and_audit_evidence(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('backups.store'))->assertRedirect();

        $context = app(TenantContext::class);
        $context->setPlatform();

        $activity = ActivityLog::query()
            ->where('module', 'backups')
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertSame('Manual platform backup completed.', $activity->description);

        $audit = AuditLog::query()
            ->where('auditable_type', BackupLog::class)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertArrayHasKey('status', $audit->new_values);
        $this->assertArrayNotHasKey('file_path', $audit->new_values);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_backup_history_filters_are_validated(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('backups.index', ['status' => 'archived']))
            ->assertSessionHasErrors('status');
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'BKP-'.$key,
            'email' => 'backup-'.$key.'@example.com',
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
}
