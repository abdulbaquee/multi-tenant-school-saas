<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\UserService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantAwareServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_every_user_service_entry_point_rejects_unresolved_context(): void
    {
        $school = $this->school('One');
        $actor = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $subject = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $service = app(UserService::class);

        $operations = [
            fn () => $service->listFor($actor),
            fn () => $service->assignableRolesFor($actor),
            fn () => $service->availableSchoolsFor($actor, $subject),
            fn () => $service->create([], $actor),
            fn () => $service->update($subject, [], $actor),
            fn () => $service->activate($subject, $actor),
            fn () => $service->deactivate($subject, $actor),
        ];

        foreach ($operations as $operation) {
            $this->assertAuthorizationDenied($operation);
        }
    }

    public function test_user_service_rejects_mismatched_or_incorrect_context_modes(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $context = app(TenantContext::class);
        $service = app(UserService::class);

        $context->setTenant($schoolTwo->id);
        $this->assertAuthorizationDenied(fn () => $service->listFor($schoolAdmin));

        $context->setPlatform();
        $this->assertAuthorizationDenied(fn () => $service->listFor($schoolAdmin));

        $context->setTenant($schoolOne->id);
        $this->assertAuthorizationDenied(fn () => $service->listFor($superAdmin));
    }

    public function test_user_service_allows_matching_tenant_and_platform_contexts(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $schoolOneTeacher = $this->schoolUser(Role::TEACHER, $schoolOne, 'teacher-one@example.com');
        $schoolTwoTeacher = $this->schoolUser(Role::TEACHER, $schoolTwo, 'teacher-two@example.com');
        $superAdmin = $this->superAdmin();
        $context = app(TenantContext::class);
        $service = app(UserService::class);

        $context->setTenant($schoolOne->id);
        $tenantUserIds = collect($service->listFor($schoolAdmin)->items())->pluck('id');

        $this->assertTrue($tenantUserIds->contains($schoolAdmin->id));
        $this->assertTrue($tenantUserIds->contains($schoolOneTeacher->id));
        $this->assertFalse($tenantUserIds->contains($schoolTwoTeacher->id));

        $context->setPlatform();
        $platformUserIds = collect($service->listFor($superAdmin)->items())->pluck('id');

        $this->assertTrue($platformUserIds->contains($schoolOneTeacher->id));
        $this->assertTrue($platformUserIds->contains($schoolTwoTeacher->id));
    }

    public function test_user_service_rejects_direct_administrator_self_password_reset(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        app(TenantContext::class)->setTenant($school->id);

        $this->assertAuthorizationDenied(fn () => app(UserService::class)->update(
            $schoolAdmin,
            [
                'name' => $schoolAdmin->name,
                'email' => $schoolAdmin->email,
                'role_id' => $schoolAdmin->role_id,
                'password' => 'ChangedPassword123',
            ],
            $schoolAdmin,
        ));

        $this->assertTrue(Hash::check('password', $schoolAdmin->fresh()->password));
    }

    public function test_dashboard_service_rejects_unresolved_mismatched_and_incorrect_context_modes(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $context = app(TenantContext::class);
        $service = app(DashboardService::class);

        $this->assertAuthorizationDenied(fn () => $service->summaryFor($schoolAdmin));
        $this->assertAuthorizationDenied(fn () => $service->summaryFor($superAdmin));

        $context->setTenant($schoolTwo->id);
        $this->assertAuthorizationDenied(fn () => $service->summaryFor($schoolAdmin));

        $context->setPlatform();
        $this->assertAuthorizationDenied(fn () => $service->summaryFor($schoolAdmin));

        $context->setTenant($schoolOne->id);
        $this->assertAuthorizationDenied(fn () => $service->summaryFor($superAdmin));
    }

    public function test_dashboard_service_allows_matching_tenant_and_platform_contexts(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $superAdmin = $this->superAdmin();
        $context = app(TenantContext::class);
        $service = app(DashboardService::class);

        $context->setTenant($school->id);
        $tenantSummary = $service->summaryFor($schoolAdmin);

        $this->assertSame('School administration', $tenantSummary['title']);
        $this->assertSame(2, $tenantSummary['metrics'][0]['value']);

        $context->setPlatform();
        $platformSummary = $service->summaryFor($superAdmin);

        $this->assertSame('Platform overview', $platformSummary['title']);
        $this->assertSame(1, $platformSummary['metrics'][0]['value']);
    }

    private function assertAuthorizationDenied(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the service to reject the tenant context.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'SCH-'.$key,
            'email' => 'school-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'role_id' => $this->roleId($roleCode),
            'school_id' => $school->id,
            'email' => $email,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function roleId(string $code): int
    {
        return (int) Role::query()->where('code', $code)->value('id');
    }
}
