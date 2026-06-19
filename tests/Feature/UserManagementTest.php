<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_super_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('users.index'));

        $response->assertOk();
    }

    public function test_school_admin_can_only_view_users_in_own_school(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $schoolOne, 'admin1@example.com');
        $visibleUser = $this->userWithRole(Role::TEACHER, $schoolOne, 'teacher1@example.com');
        $hiddenUser = $this->userWithRole(Role::TEACHER, $schoolTwo, 'teacher2@example.com');

        $response = $this->actingAs($schoolAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee($visibleUser->email);
        $response->assertDontSee($hiddenUser->email);
    }

    public function test_school_admin_cannot_access_another_schools_user_record(): void
    {
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $this->school('One'), 'admin1@example.com');
        $otherUser = $this->userWithRole(Role::TEACHER, $this->school('Two'), 'teacher2@example.com');

        $this->actingAs($schoolAdmin)->get(route('users.show', $otherUser))->assertForbidden();
        $this->actingAs($schoolAdmin)->get(route('users.edit', $otherUser))->assertForbidden();
        $this->actingAs($schoolAdmin)->put(route('users.update', $otherUser), [
            'name' => 'Changed Name',
            'email' => $otherUser->email,
            'role_id' => $this->roleId(Role::TEACHER),
        ])->assertForbidden();
        $this->actingAs($schoolAdmin)->patch(route('users.deactivate', $otherUser))->assertForbidden();

        $otherUser->update(['status' => User::STATUS_INACTIVE]);
        $this->actingAs($schoolAdmin)->patch(route('users.activate', $otherUser))->assertForbidden();
    }

    public function test_teacher_and_accountant_cannot_access_user_management_actions(): void
    {
        foreach ([Role::TEACHER, Role::ACCOUNTANT] as $index => $roleCode) {
            $school = $this->school('Restricted '.$index);
            $actor = $this->userWithRole($roleCode, $school, $roleCode.'@example.com');
            $subject = $this->userWithRole(Role::TEACHER, $school, 'subject'.$index.'@example.com');

            $this->actingAs($actor)->get(route('users.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('users.create'))->assertForbidden();

            // A duplicate email proves authorization runs before validation.
            $this->actingAs($actor)->post(route('users.store'), [
                ...$this->validUserPayload($this->roleId(Role::TEACHER)),
                'email' => $subject->email,
            ])->assertForbidden();

            $this->actingAs($actor)->get(route('users.show', $subject))->assertForbidden();
            $this->actingAs($actor)->get(route('users.edit', $subject))->assertForbidden();
            $this->actingAs($actor)->put(route('users.update', $subject), [
                'name' => 'Unauthorized Change',
                'email' => $subject->email,
                'role_id' => $this->roleId(Role::TEACHER),
            ])->assertForbidden();
            $this->actingAs($actor)->patch(route('users.deactivate', $subject))->assertForbidden();

            $subject->update(['status' => User::STATUS_INACTIVE]);
            $this->actingAs($actor)->patch(route('users.activate', $subject))->assertForbidden();
        }
    }

    public function test_school_admin_cannot_edit_super_admin(): void
    {
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $this->school('One'), 'admin1@example.com');

        $this->actingAs($schoolAdmin)
            ->get(route('users.edit', $this->superAdmin()))
            ->assertForbidden();
    }

    public function test_school_admin_can_create_a_school_user_with_derived_school_id(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin1@example.com');

        $response = $this->actingAs($schoolAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'newteacher@example.com',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newteacher@example.com',
            'school_id' => $school->id,
            'role_id' => $this->roleId(Role::TEACHER),
        ]);
    }

    public function test_school_admin_cannot_submit_a_school_id_or_assign_super_admin_role(): void
    {
        $school = $this->school('One');
        $otherSchool = $this->school('Two');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin1@example.com');

        $this->actingAs($schoolAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'forgedschool@example.com',
            'school_id' => $otherSchool->id,
        ])->assertSessionHasErrors('school_id');

        $this->actingAs($schoolAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::SUPER_ADMIN)),
            'email' => 'forgedadmin@example.com',
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'forgedschool@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'forgedadmin@example.com']);
    }

    public function test_school_admin_role_selector_excludes_super_admin(): void
    {
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $this->school('One'), 'admin1@example.com');

        $response = $this->actingAs($schoolAdmin)->get(route('users.create'));

        $response->assertOk();
        $response->assertDontSee('Super Admin');
        $response->assertSee('School Admin');
        $response->assertSee('Teacher');
        $response->assertSee('Accountant');
    }

    public function test_super_admin_can_create_school_user_with_a_school(): void
    {
        $school = $this->school('One');

        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'newuser@example.com',
            'school_id' => $school->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'school_id' => $school->id,
            'role_id' => $this->roleId(Role::TEACHER),
        ]);
    }

    public function test_role_and_school_combinations_are_validated(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'schoolless@example.com',
        ])->assertSessionHasErrors('school_id');

        $this->actingAs($superAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::SUPER_ADMIN)),
            'email' => 'scopedadmin@example.com',
            'school_id' => $school->id,
        ])->assertSessionHasErrors('school_id');

        $this->assertDatabaseMissing('users', ['email' => 'schoolless@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'scopedadmin@example.com']);
    }

    public function test_malformed_scoped_super_admin_does_not_receive_platform_access(): void
    {
        $scopedSuperAdmin = $this->userWithRole(
            Role::SUPER_ADMIN,
            $this->school('One'),
            'scopedadmin@example.com',
        );

        $this->assertFalse($scopedSuperAdmin->isSuperAdmin());
        $this->actingAs($scopedSuperAdmin)->get(route('users.index'))->assertForbidden();
    }

    public function test_user_cannot_change_own_role(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin1@example.com');

        $response = $this->actingAs($schoolAdmin)->put(route('users.update', $schoolAdmin), [
            'name' => $schoolAdmin->name,
            'email' => $schoolAdmin->email,
            'role_id' => $this->roleId(Role::TEACHER),
        ]);

        $response->assertSessionHasErrors('role_id');
        $this->assertSame($this->roleId(Role::SCHOOL_ADMIN), $schoolAdmin->fresh()->role_id);
    }

    public function test_user_password_must_follow_documented_complexity_rules(): void
    {
        $school = $this->school('One');

        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'weakpassword@example.com',
            'school_id' => $school->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weakpassword@example.com']);
    }

    public function test_user_can_be_deactivated_and_reactivated_without_deletion(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();
        $user = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $user->forceFill(['remember_token' => 'remember-me'])->save();
        DB::table('sessions')->insert([
            'id' => 'user-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('users.deactivate', $user))
            ->assertRedirect(route('users.index'));

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame(User::STATUS_INACTIVE, $user->fresh()->status);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

        $this->actingAs($superAdmin)
            ->patch(route('users.activate', $user))
            ->assertRedirect(route('users.index'));

        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
    }

    public function test_user_cannot_deactivate_own_account(): void
    {
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $this->school('One'), 'admin1@example.com');

        $this->actingAs($schoolAdmin)
            ->patch(route('users.deactivate', $schoolAdmin))
            ->assertForbidden();

        $this->assertSame(User::STATUS_ACTIVE, $schoolAdmin->fresh()->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function validUserPayload(int $roleId): array
    {
        return [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'phone' => '9123456789',
            'role_id' => $roleId,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];
    }

    private function school(string $suffix): School
    {
        $key = strtolower(str_replace(' ', '-', $suffix));

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
