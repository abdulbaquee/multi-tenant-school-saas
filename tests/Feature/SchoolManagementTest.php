<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guests_cannot_access_school_management(): void
    {
        $school = $this->school('One');

        $this->get(route('schools.index'))->assertRedirect(route('login'));
        $this->post(route('schools.store'), $this->validSchoolPayload())->assertRedirect(route('login'));
        $this->get(route('schools.show', $school))->assertRedirect(route('login'));
        $this->put(route('schools.update', $school), $this->validSchoolPayload())->assertRedirect(route('login'));
        $this->patch(route('schools.deactivate', $school), ['reason' => 'Test'])->assertRedirect(route('login'));
    }

    public function test_school_roles_are_denied_every_school_management_action(): void
    {
        foreach ([Role::SCHOOL_ADMIN, Role::TEACHER, Role::ACCOUNTANT] as $index => $roleCode) {
            $actorSchool = $this->school('Actor '.$index);
            $school = $this->school('Restricted '.$index);
            $actor = $this->schoolUser($roleCode, $actorSchool, $roleCode.$index.'@example.com');

            $this->actingAs($actor)->get(route('schools.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('schools.create'))->assertForbidden();
            $this->actingAs($actor)->post(route('schools.store'), $this->validSchoolPayload())->assertForbidden();
            $this->actingAs($actor)->get(route('schools.show', $school))->assertForbidden();
            $this->actingAs($actor)->get(route('schools.edit', $school))->assertForbidden();
            $this->actingAs($actor)->put(route('schools.update', $school), $this->validSchoolPayload())->assertForbidden();
            $this->actingAs($actor)->patch(route('schools.deactivate', $school), ['reason' => 'Unauthorized'])->assertForbidden();

            $school->update(['status' => School::STATUS_INACTIVE]);
            $this->actingAs($actor)->patch(route('schools.activate', $school))->assertForbidden();
        }
    }

    public function test_super_admin_can_search_and_filter_the_school_register(): void
    {
        $visible = $this->school('Green Valley', ['city' => 'Delhi']);
        $hidden = $this->school('Blue Ridge', ['city' => 'Mumbai']);
        $hidden->update(['status' => School::STATUS_INACTIVE]);

        $response = $this->actingAs($this->superAdmin())->get(route('schools.index', [
            'search' => 'Green',
            'status' => School::STATUS_ACTIVE,
        ]));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee($hidden->name);
        $response->assertSee('New School');
    }

    public function test_school_register_filters_are_validated(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('schools.index', ['status' => 'deleted']))
            ->assertSessionHasErrors('status');
    }

    public function test_super_admin_can_create_a_normalized_school_with_default_settings(): void
    {
        $response = $this->actingAs($this->superAdmin())->post(route('schools.store'), [
            ...$this->validSchoolPayload(),
            'name' => '  Sunrise Public School  ',
            'code' => '  sun-01  ',
            'email' => '  OFFICE@SUNRISE.EXAMPLE  ',
        ]);

        $school = School::query()->where('code', 'SUN-01')->firstOrFail();

        $response->assertRedirect(route('schools.show', $school));
        $response->assertSessionHas('status', 'School created successfully.');
        $this->assertSame('Sunrise Public School', $school->name);
        $this->assertSame('office@sunrise.example', $school->email);
        $this->assertSame(School::STATUS_ACTIVE, $school->status);
        $this->assertNull($school->deactivated_at);
        $this->assertDatabaseHas('school_settings', [
            'school_id' => $school->id,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'academic_year_start_month' => 4,
            'grading_system' => 'percentage',
        ]);
        $this->assertDatabaseCount('school_settings', 1);
    }

    public function test_required_unique_and_lifecycle_fields_are_validated(): void
    {
        $existing = $this->school('Existing');

        $this->actingAs($this->superAdmin())
            ->post(route('schools.store'), [])
            ->assertSessionHasErrors(['name', 'code', 'email']);

        $this->actingAs($this->superAdmin())
            ->post(route('schools.store'), [
                ...$this->validSchoolPayload(),
                'code' => strtolower($existing->code),
                'email' => strtoupper($existing->email),
                'status' => School::STATUS_INACTIVE,
                'deactivated_at' => now()->toDateTimeString(),
                'deactivation_reason' => 'Forged lifecycle',
                'deleted_at' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors([
                'code',
                'email',
                'status',
                'deactivated_at',
                'deactivation_reason',
                'deleted_at',
            ]);
    }

    public function test_super_admin_can_view_and_update_school_information_only(): void
    {
        $school = $this->school('One');

        $this->actingAs($this->superAdmin())
            ->get(route('schools.show', $school))
            ->assertOk()
            ->assertSee($school->name)
            ->assertSee('Default Settings');

        $response = $this->actingAs($this->superAdmin())->put(route('schools.update', $school), [
            ...$this->validSchoolPayload(),
            'name' => 'Updated School',
            'code' => 'updated-code',
            'email' => 'UPDATED@EXAMPLE.COM',
            'city' => 'Kolkata',
        ]);

        $response->assertRedirect(route('schools.show', $school));
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Updated School',
            'code' => 'UPDATED-CODE',
            'email' => 'updated@example.com',
            'city' => 'Kolkata',
            'status' => School::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin())->put(route('schools.update', $school), [
            ...$this->validSchoolPayload(),
            'status' => School::STATUS_INACTIVE,
        ])->assertSessionHasErrors('status');

        $this->assertSame(School::STATUS_ACTIVE, $school->fresh()->status);
    }

    public function test_deactivation_requires_a_reason(): void
    {
        $school = $this->school('One');

        $this->actingAs($this->superAdmin())
            ->patch(route('schools.deactivate', $school), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(School::STATUS_ACTIVE, $school->fresh()->status);
    }

    public function test_deactivation_revokes_only_school_sessions_and_preserves_records(): void
    {
        $school = $this->school('One');
        $otherSchool = $this->school('Two');
        $schoolUser = $this->schoolUser(Role::TEACHER, $school, 'teacher1@example.com');
        $secondSchoolUser = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant1@example.com');
        $otherUser = $this->schoolUser(Role::TEACHER, $otherSchool, 'teacher2@example.com');

        foreach ([$schoolUser, $secondSchoolUser, $otherUser] as $index => $user) {
            $user->forceFill(['remember_token' => 'remember-'.$index])->save();
            $this->sessionRecord('session-'.$index, $user);
        }

        $response = $this->actingAs($this->superAdmin())
            ->patch(route('schools.deactivate', $school), ['reason' => 'Annual subscription ended.']);

        $response->assertRedirect(route('schools.show', $school));
        $school->refresh();
        $this->assertSame(School::STATUS_INACTIVE, $school->status);
        $this->assertNotNull($school->deactivated_at);
        $this->assertSame('Annual subscription ended.', $school->deactivation_reason);
        $this->assertNotSoftDeleted('schools', ['id' => $school->id]);
        $this->assertDatabaseHas('users', ['id' => $schoolUser->id]);
        $this->assertDatabaseHas('users', ['id' => $secondSchoolUser->id]);
        $this->assertDatabaseHas('school_settings', ['school_id' => $school->id]);
        $this->assertNull($schoolUser->fresh()->remember_token);
        $this->assertNull($secondSchoolUser->fresh()->remember_token);
        $this->assertSame('remember-2', $otherUser->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $schoolUser->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $secondSchoolUser->id]);
        $this->assertDatabaseHas('sessions', ['user_id' => $otherUser->id]);
    }

    public function test_deactivated_school_user_is_signed_out_on_the_next_request(): void
    {
        $school = $this->school('One');
        $user = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($this->superAdmin())
            ->patch(route('schools.deactivate', $school), ['reason' => 'Temporary closure.']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_reactivation_clears_school_metadata_without_activating_users(): void
    {
        $school = $this->school('One');
        $activeUser = $this->schoolUser(Role::TEACHER, $school, 'active@example.com');
        $inactiveUser = $this->schoolUser(Role::ACCOUNTANT, $school, 'inactive@example.com');
        $inactiveUser->update(['status' => User::STATUS_INACTIVE]);
        $school->update([
            'status' => School::STATUS_INACTIVE,
            'deactivated_at' => now(),
            'deactivation_reason' => 'Temporary closure.',
        ]);

        $this->actingAs($this->superAdmin())
            ->patch(route('schools.activate', $school))
            ->assertRedirect(route('schools.show', $school));

        $school->refresh();
        $this->assertSame(School::STATUS_ACTIVE, $school->status);
        $this->assertNull($school->deactivated_at);
        $this->assertNull($school->deactivation_reason);
        $this->assertSame(User::STATUS_ACTIVE, $activeUser->fresh()->status);
        $this->assertSame(User::STATUS_INACTIVE, $inactiveUser->fresh()->status);

        $this->post(route('login'), ['email' => $activeUser->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_invalid_lifecycle_transitions_are_forbidden(): void
    {
        $school = $this->school('One');

        $this->actingAs($this->superAdmin())
            ->patch(route('schools.activate', $school))
            ->assertForbidden();

        $school->update(['status' => School::STATUS_INACTIVE]);

        $this->actingAs($this->superAdmin())
            ->patch(route('schools.deactivate', $school), ['reason' => 'Duplicate'])
            ->assertForbidden();
    }

    public function test_inactive_school_cannot_receive_a_new_user(): void
    {
        $school = $this->school('One');
        $school->update(['status' => School::STATUS_INACTIVE]);

        $response = $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'New Teacher',
            'email' => 'newteacher@example.com',
            'phone' => null,
            'role_id' => $this->roleId(Role::TEACHER),
            'school_id' => $school->id,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('school_id');
        $this->assertDatabaseMissing('users', ['email' => 'newteacher@example.com']);

        $this->actingAs($this->superAdmin())
            ->get(route('users.create'))
            ->assertDontSee($school->name);
    }

    public function test_school_navigation_is_visible_only_to_super_admin(): void
    {
        $school = $this->school('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('schools.index'), false);

        $this->actingAs($schoolAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('schools.index'), false);
    }

    public function test_school_has_no_normal_delete_endpoint(): void
    {
        $school = $this->school('One');

        $this->actingAs($this->superAdmin())
            ->delete('/schools/'.$school->id)
            ->assertMethodNotAllowed();

        $this->assertNotSoftDeleted('schools', ['id' => $school->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function school(string $suffix, array $attributes = []): School
    {
        $key = strtolower(str_replace(' ', '-', $suffix));

        $school = School::create([
            'name' => 'School '.$suffix,
            'code' => strtoupper('SCH-'.$key),
            'email' => 'school-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
            ...$attributes,
        ]);

        DB::table('school_settings')->insert([
            'school_id' => $school->id,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'academic_year_start_month' => 4,
            'grading_system' => 'percentage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $school;
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

    /**
     * @return array<string, mixed>
     */
    private function validSchoolPayload(): array
    {
        return [
            'name' => 'New School',
            'code' => 'NEW-01',
            'email' => 'school-new@example.com',
            'phone' => '9123456789',
            'address' => '1 Learning Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'country' => 'India',
            'postal_code' => '700001',
            'principal_name' => 'Principal Name',
            'website' => 'https://school.example.com',
        ];
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
}
