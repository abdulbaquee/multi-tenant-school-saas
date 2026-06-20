<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_cannot_access_or_create_users(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));

        $this->post(route('users.store'), $this->validUserPayload($this->roleId(Role::TEACHER)))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);
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

    public function test_school_admin_can_view_and_update_a_user_in_own_school(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin1@example.com');
        $teacher = $this->userWithRole(Role::TEACHER, $school, 'teacher1@example.com');

        $this->actingAs($schoolAdmin)
            ->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->email);

        $response = $this->actingAs($schoolAdmin)->put(route('users.update', $teacher), [
            'name' => 'Updated Teacher',
            'email' => $teacher->email,
            'phone' => '9876543210',
            'role_id' => $this->roleId(Role::TEACHER),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'school_id' => $school->id,
            'name' => 'Updated Teacher',
            'phone' => '9876543210',
        ]);
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
            'email_verified' => '1',
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
        $response->assertSessionHas('status', 'User created successfully.');
        $this->assertDatabaseHas('users', [
            'email' => 'newteacher@example.com',
            'school_id' => $school->id,
            'role_id' => $this->roleId(Role::TEACHER),
        ]);
        $createdUser = User::query()->where('email', 'newteacher@example.com')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'module' => 'user_management',
            'action' => 'created',
            'subject_id' => $createdUser->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'auditable_type' => User::class,
            'auditable_id' => $createdUser->id,
            'event' => 'created',
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

    public function test_super_admin_can_control_email_verification_for_managed_users(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::TEACHER)),
            'email' => 'verified-teacher@example.com',
            'school_id' => $school->id,
            'email_verified' => '1',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'verified-teacher@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);

        $this->actingAs($superAdmin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => $school->id,
            'email_verified' => '0',
        ])->assertRedirect(route('users.index'));

        $this->assertNull($user->fresh()->email_verified_at);

        $this->actingAs($superAdmin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => $school->id,
            'email_verified' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_super_admin_can_create_an_unverified_user(): void
    {
        $school = $this->school('One');

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::ACCOUNTANT)),
            'email' => 'unverified-accountant@example.com',
            'school_id' => $school->id,
            'email_verified' => '0',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'unverified-accountant@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
    }

    public function test_school_admin_can_control_email_verification_within_own_school(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $teacher->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($schoolAdmin)->put(route('users.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'role_id' => $this->roleId(Role::TEACHER),
            'email_verified' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertNotNull($teacher->fresh()->email_verified_at);

        $this->actingAs($schoolAdmin)->put(route('users.update', $teacher), [
            'name' => $teacher->name,
            'email' => 'changed-teacher@example.com',
            'role_id' => $this->roleId(Role::TEACHER),
            'email_verified' => '0',
        ])->assertRedirect(route('users.index'));

        $teacher->refresh();
        $this->assertSame('changed-teacher@example.com', $teacher->email);
        $this->assertNull($teacher->email_verified_at);

        $this->actingAs($schoolAdmin)->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::ACCOUNTANT)),
            'email' => 'verified-accountant@example.com',
            'email_verified' => '1',
        ])->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('email', 'verified-accountant@example.com')->firstOrFail();
        $this->assertSame($school->id, $createdUser->school_id);
        $this->assertNotNull($createdUser->email_verified_at);
    }

    public function test_email_verification_checkbox_is_visible_to_both_administrator_roles(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($this->superAdmin())
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Mark email as verified');

        $this->actingAs($schoolAdmin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Mark email as verified');
    }

    public function test_unverified_school_admin_cannot_attest_emails_or_self_verify(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $schoolAdmin->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($schoolAdmin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('Mark email as verified');

        $this->actingAs($schoolAdmin)->put(route('users.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'role_id' => $this->roleId(Role::TEACHER),
            'email_verified' => '1',
        ])->assertSessionHasErrors('email_verified');

        $schoolAdmin->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($schoolAdmin)
            ->get(route('users.edit', $schoolAdmin))
            ->assertOk()
            ->assertDontSee('Mark email as verified');

        $this->actingAs($schoolAdmin)->put(route('users.update', $schoolAdmin), [
            'name' => $schoolAdmin->name,
            'email' => $schoolAdmin->email,
            'role_id' => $this->roleId(Role::SCHOOL_ADMIN),
            'email_verified' => '0',
        ])->assertSessionHasErrors('email_verified');

        $this->assertNotNull($schoolAdmin->fresh()->email_verified_at);
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
        $this->actingAs($scopedSuperAdmin)
            ->get(route('users.index'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
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

    public function test_super_admin_can_update_optional_fields_without_changing_password(): void
    {
        $school = $this->school('One');
        $user = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $originalPassword = $user->password;

        $response = $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9876543210',
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => $school->id,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'User updated successfully.');
        $this->assertSame('9876543210', $user->fresh()->phone);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_cross_school_user_reassignment_history_is_visible_only_in_platform_context(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $user = $this->userWithRole(Role::TEACHER, $schoolOne, 'teacher@example.com');
        $otherUser = $this->userWithRole(Role::ACCOUNTANT, $schoolOne, 'accountant@example.com');
        $user->forceFill(['remember_token' => 'moving-user-remember'])->save();
        $otherUser->forceFill(['remember_token' => 'other-user-remember'])->save();
        $this->sessionRecord('moving-user-session', $user);
        $this->sessionRecord('other-user-session', $otherUser);

        $this->actingAs($this->superAdmin())->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => $schoolTwo->id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($schoolTwo->id, $user->fresh()->school_id);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame('other-user-remember', $otherUser->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'moving-user-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);

        $context = app(TenantContext::class);
        $context->setPlatform();

        $activity = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest('id')
            ->firstOrFail();
        $audit = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNull($activity->school_id);
        $this->assertNull($audit->school_id);
        $this->assertSame($schoolOne->id, $audit->old_values['school_id']);
        $this->assertSame($schoolTwo->id, $audit->new_values['school_id']);

        foreach ([$schoolOne, $schoolTwo] as $school) {
            $context->setTenant($school->id);
            $this->assertFalse(ActivityLog::query()->whereKey($activity->id)->exists());
            $this->assertFalse(AuditLog::query()->whereKey($audit->id)->exists());
        }
    }

    public function test_edit_form_explains_that_password_is_optional(): void
    {
        $school = $this->school('One');
        $user = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');

        $response = $this->actingAs($this->superAdmin())->get(route('users.edit', $user));

        $response->assertOk();
        $response->assertSee('New Password (optional)');
        $response->assertSee('Leave blank to keep the current password.');
        $response->assertSee('Required fields');
        $response->assertSee('autocomplete="new-password"', false);
    }

    public function test_administrator_cannot_reset_own_password_through_user_management(): void
    {
        $schoolAdmin = $this->userWithRole(Role::SCHOOL_ADMIN, $this->school('One'), 'admin@example.com');
        $originalPassword = $schoolAdmin->password;

        $this->actingAs($schoolAdmin)
            ->get(route('users.edit', $schoolAdmin))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertSee('Use your Profile page to change your own password');

        $this->actingAs($schoolAdmin)
            ->put(route('users.update', $schoolAdmin), [
                'name' => $schoolAdmin->name,
                'email' => $schoolAdmin->email,
                'role_id' => $this->roleId(Role::SCHOOL_ADMIN),
                'password' => 'ChangedPassword123',
                'password_confirmation' => 'ChangedPassword123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($originalPassword, $schoolAdmin->fresh()->password);
    }

    public function test_role_assignment_revokes_only_the_target_users_sessions_and_remember_token(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();
        $target = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $otherUser = $this->userWithRole(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $target->forceFill(['remember_token' => 'target-remember'])->save();
        $otherUser->forceFill(['remember_token' => 'other-remember'])->save();
        $this->sessionRecord('target-role-session', $target);
        $this->sessionRecord('other-role-session', $otherUser);

        $this->actingAs($superAdmin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $this->roleId(Role::ACCOUNTANT),
            'school_id' => $school->id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($this->roleId(Role::ACCOUNTANT), $target->fresh()->role_id);
        $this->assertNull($target->fresh()->remember_token);
        $this->assertSame('other-remember', $otherUser->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-role-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-role-session']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'role_assigned',
            'subject_id' => $target->id,
        ]);
    }

    public function test_administrative_password_reset_revokes_only_target_sessions_and_credentials_are_not_logged(): void
    {
        $school = $this->school('One');
        $superAdmin = $this->superAdmin();
        $target = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');
        $otherUser = $this->userWithRole(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $target->forceFill(['remember_token' => 'target-remember'])->save();
        $otherUser->forceFill(['remember_token' => 'other-remember'])->save();
        $this->sessionRecord('target-session', $target);
        $this->sessionRecord('other-session', $otherUser);

        $this->actingAs($superAdmin)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role_id' => $this->roleId(Role::TEACHER),
                'school_id' => $school->id,
                'password' => 'ResetPassword123',
                'password_confirmation' => 'ResetPassword123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('ResetPassword123', $target->fresh()->password));
        $this->assertNull($target->fresh()->remember_token);
        $this->assertSame('other-remember', $otherUser->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);

        $auditValues = DB::table('audit_logs')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->latest('id')
            ->value('new_values');

        $this->assertIsString($auditValues);
        $this->assertStringNotContainsStringIgnoringCase('password123', $auditValues);
        $this->assertStringNotContainsStringIgnoringCase('remember', $auditValues);
    }

    public function test_weak_administrative_password_reset_is_rejected(): void
    {
        $school = $this->school('One');
        $target = $this->userWithRole(Role::TEACHER, $school, 'teacher@example.com');

        $this->actingAs($this->superAdmin())
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role_id' => $this->roleId(Role::TEACHER),
                'school_id' => $school->id,
                'password' => 'weakpassword',
                'password_confirmation' => 'weakpassword',
            ])
            ->assertSessionHasErrors('password');
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

    public function test_required_user_fields_are_validated(): void
    {
        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'role_id', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_email_must_be_globally_unique(): void
    {
        $school = $this->school('One');
        $existingUser = $this->userWithRole(Role::TEACHER, $school, 'existing@example.com');

        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), [
            ...$this->validUserPayload($this->roleId(Role::ACCOUNTANT)),
            'email' => $existingUser->email,
            'school_id' => $school->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
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

    private function sessionRecord(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);
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
