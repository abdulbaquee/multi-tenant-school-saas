<?php

namespace Tests\Feature;

use App\Http\Middleware\TenantContextMiddleware;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class TenantContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Route::middleware(['web', 'auth', 'tenant.context'])
            ->get('/_test/tenant-context', function (TenantContext $context) {
                return response()->json([
                    'state' => $context->state()->value,
                    'school_id' => $context->schoolId(),
                ]);
            });

        Route::middleware(['web', 'auth', 'tenant.context'])
            ->get('/_test/tenant-context/exception', function (): never {
                throw new RuntimeException('Lifecycle test exception.');
            });
    }

    public function test_school_user_receives_tenant_context_and_it_is_cleared_after_request(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->actingAs($user)
            ->get('/_test/tenant-context')
            ->assertOk()
            ->assertJson([
                'state' => 'tenant',
                'school_id' => $school->id,
            ]);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_super_admin_receives_explicit_platform_context(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/_test/tenant-context')
            ->assertOk()
            ->assertJson([
                'state' => 'platform',
                'school_id' => null,
            ]);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_sequential_requests_do_not_reuse_another_schools_context(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $userOne = $this->schoolUser(Role::TEACHER, $schoolOne, 'teacher1@example.com');
        $userTwo = $this->schoolUser(Role::ACCOUNTANT, $schoolTwo, 'accountant2@example.com');

        $this->actingAs($userOne)
            ->get('/_test/tenant-context')
            ->assertJson(['school_id' => $schoolOne->id]);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());

        $this->actingAs($userTwo)
            ->get('/_test/tenant-context')
            ->assertJson(['school_id' => $schoolTwo->id]);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_context_is_cleared_when_downstream_code_throws(): void
    {
        $user = $this->schoolUser(Role::TEACHER, $this->school('One'), 'teacher@example.com');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->get('/_test/tenant-context/exception');
            $this->fail('The lifecycle test exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Lifecycle test exception.', $exception->getMessage());
        }

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_inactive_school_user_is_logged_out_and_context_remains_unresolved(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $school->update(['status' => School::STATUS_INACTIVE]);

        $this->actingAs($user)
            ->get('/_test/tenant-context')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_inactive_user_session_is_rejected(): void
    {
        $user = $this->schoolUser(
            Role::TEACHER,
            $this->school('One'),
            'teacher@example.com',
        );
        $user->update(['status' => User::STATUS_INACTIVE]);

        $this->actingAs($user)
            ->get('/_test/tenant-context')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_soft_deleted_school_user_session_is_rejected(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $school->delete();

        $this->actingAs($user)
            ->get('/_test/tenant-context')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_soft_deleted_school_user_cannot_authenticate(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $school->delete();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_school_user_cannot_authenticate(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $school->update(['status' => School::STATUS_INACTIVE]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_school_user_without_school_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => null,
            'email' => 'schoolless@example.com',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_malformed_users_cannot_establish_tenant_or_platform_context(): void
    {
        $school = $this->school('One');
        $teacherWithoutSchool = User::factory()->create([
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => null,
        ]);
        $scopedSuperAdmin = $this->schoolUser(
            Role::SUPER_ADMIN,
            $school,
            'scoped-superadmin@example.com',
        );

        $this->actingAs($teacherWithoutSchool)
            ->get('/_test/tenant-context')
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($scopedSuperAdmin)
            ->get('/_test/tenant-context')
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_tenant_context_middleware_runs_after_authentication_and_before_bindings(): void
    {
        $priority = app(HttpKernel::class)->getMiddlewarePriority();
        $authIndex = array_search(AuthenticatesRequests::class, $priority, true);
        $tenantIndex = array_search(TenantContextMiddleware::class, $priority, true);
        $bindingsIndex = array_search(SubstituteBindings::class, $priority, true);

        $this->assertIsInt($authIndex);
        $this->assertIsInt($tenantIndex);
        $this->assertIsInt($bindingsIndex);
        $this->assertTrue($authIndex < $tenantIndex);
        $this->assertTrue($tenantIndex < $bindingsIndex);
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
