<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Reporting\ReportCategory;
use App\Services\ReportCsvExportService;
use App\Services\ReportService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_from_reports_hub(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_super_admin_sees_platform_report_categories(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Platform reports')
            ->assertSee('School Reports')
            ->assertSee('User Reports')
            ->assertSee('Student Reports')
            ->assertSee('Attendance Reports')
            ->assertSee('Fee Reports')
            ->assertSee('Examination Reports');
    }

    public function test_school_admin_sees_school_scoped_report_categories_only(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('School reports')
            ->assertSee('Student Reports')
            ->assertSee('Attendance Reports')
            ->assertSee('Fee Reports')
            ->assertSee('Examination Reports')
            ->assertSee('User Reports')
            ->assertDontSee('School Reports');
    }

    public function test_teacher_sees_assigned_report_categories_only(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $response = $this->actingAs($teacher)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Student Reports')
            ->assertSee('Attendance Reports')
            ->assertSee('Examination Reports')
            ->assertDontSee('Fee Reports')
            ->assertDontSee('User Reports')
            ->assertDontSee('School Reports');
    }

    public function test_accountant_sees_financial_report_categories_only(): void
    {
        $school = $this->school('One');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $response = $this->actingAs($accountant)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Fee Reports')
            ->assertSee('Student Reports')
            ->assertDontSee('Attendance Reports')
            ->assertDontSee('Examination Reports')
            ->assertDontSee('User Reports')
            ->assertDontSee('School Reports');
    }

    public function test_reports_hub_rejects_forged_scope_filters(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)
            ->get(route('reports.index', ['school_id' => $school->id]))
            ->assertSessionHasErrors('school_id');
    }

    public function test_reports_hub_is_hidden_without_reports_view_permission(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->removePermission($teacher->role, 'reports.view');

        $this->actingAs($teacher)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('dashboard'))->assertOk()->assertDontSee(route('reports.index'), false);
    }

    public function test_report_service_denies_cross_tenant_hub_access(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $otherSchool = $this->school('Two');

        $this->tenant($otherSchool, function () use ($admin): void {
            $this->expectException(AuthorizationException::class);

            app(ReportService::class)->hubFor($admin);
        });
    }

    public function test_csv_export_service_requires_export_authorization(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->tenant($school, function () use ($teacher): void {
            $this->expectException(AuthorizationException::class);

            app(ReportCsvExportService::class)->stream(
                $teacher,
                ReportCategory::Fees,
                'fee-report.csv',
                ['Student'],
                [['STU-001']],
            );
        });
    }

    public function test_csv_export_service_streams_authorized_rows(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->tenant($school, function () use ($admin): void {
            $response = app(ReportCsvExportService::class)->stream(
                $admin,
                ReportCategory::Students,
                'student-report.csv',
                ['Admission No', 'Name'],
                [['STU-001', 'Sample Student']],
            );

            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        });
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'REP-'.$key,
            'email' => 'reports-'.$key.'@example.com',
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

    private function removePermission(Role $role, string $permissionCode): void
    {
        $role->permissions()->detach(
            $role->permissions()->where('code', $permissionCode)->pluck('permissions.id'),
        );
    }
}
