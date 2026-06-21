<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_unverified_users_are_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_super_admin_sees_platform_dashboard_and_user_navigation(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Platform overview');
        $response->assertSee('Total schools');
        $response->assertSee('Active schools');
        $response->assertSee('Total users');
        $response->assertSee(route('users.index'), false);
        $response->assertSee(route('profile.edit'), false);
    }

    public function test_school_admin_dashboard_summary_is_limited_to_own_school(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $schoolOne, 'admin1@example.com');
        $this->userWithRole(Role::TEACHER, $schoolOne, 'teacher1@example.com');
        $this->userWithRole(Role::TEACHER, $schoolTwo, 'teacher2@example.com');

        $response = $this->actingAs($schoolAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('School administration');
        $response->assertSee('School One');
        $response->assertDontSee('Quick actions');
        $response->assertDontSee('Signed in as');
        $this->assertSame(1, substr_count($response->getContent(), 'School administration'));
        $response->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics[0]['label'] === 'School users'
                && $metrics[0]['value'] === 2;
        });
        $response->assertSee(route('users.index'), false);
    }

    public function test_teacher_sees_teacher_dashboard_without_user_navigation(): void
    {
        $teacher = $this->userWithRole(Role::TEACHER, $this->school('One'), 'teacher@example.com');

        $response = $this->actingAs($teacher)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Teaching workspace');
        $response->assertSee('Teacher');
        $response->assertDontSee(route('users.index'), false);
        $response->assertSee(route('profile.edit'), false);
    }

    public function test_accountant_sees_accounts_dashboard_without_user_navigation(): void
    {
        $accountant = $this->userWithRole(Role::ACCOUNTANT, $this->school('One'), 'accountant@example.com');

        $response = $this->actingAs($accountant)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Accounts workspace');
        $response->assertSee('Accountant');
        $response->assertDontSee(route('users.index'), false);
    }

    public function test_dashboard_navigation_includes_students_and_omits_later_modules_and_removed_settings(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('students.index'), false);

        foreach ([
            'Platform Settings',
            'System Settings',
            'Attendance',
            'Fees',
            'Examinations',
            'Reports',
            'Audit Logs',
            'Backup Management',
        ] as $module) {
            $response->assertDontSee($module);
        }
    }

    public function test_malformed_school_user_cannot_access_dashboard(): void
    {
        $teacherWithoutSchool = User::factory()->create([
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => null,
        ]);

        $this->actingAs($teacherWithoutSchool)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'SCH-'.$key,
            'email' => 'school-'.$key.'@example.com',
            'status' => 'active',
        ]);
    }

    private function userWithRole(string $roleCode, School $school, string $email): User
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
