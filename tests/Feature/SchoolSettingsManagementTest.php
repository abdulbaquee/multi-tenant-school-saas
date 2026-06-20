<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_cannot_access_or_update_school_settings(): void
    {
        $school = $this->school('One');

        $this->get(route('school-settings.edit'))->assertRedirect(route('login'));
        $this->patch(route('school-settings.update'), $this->validPayload($school))
            ->assertRedirect(route('login'));
    }

    public function test_school_admin_can_open_settings_and_legacy_row_is_initialized(): void
    {
        $school = $this->school('Legacy');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->assertDatabaseMissing('school_settings', ['school_id' => $school->id]);

        $this->actingAs($admin)
            ->get(route('school-settings.edit'))
            ->assertOk()
            ->assertSee('School Settings')
            ->assertSee($school->name)
            ->assertSee('General Settings')
            ->assertSee('Academic Settings')
            ->assertSee('Attendance Settings')
            ->assertSee('Grading Settings')
            ->assertSee('Logo Management')
            ->assertSee('id="settings-section-navigation"', false)
            ->assertSee('class="nav-link active"', false)
            ->assertSee('data-settings-section="general"', false);

        $this->assertDatabaseHas('school_settings', [
            'school_id' => $school->id,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'academic_year_start_month' => 4,
            'grading_system' => 'percentage',
        ]);
        $this->assertDatabaseCount('school_settings', 1);
    }

    public function test_school_admin_can_update_own_profile_and_operating_settings(): void
    {
        $school = $this->schoolWithSettings('One', [
            'settings_json' => json_encode(['week_starts_on' => 'monday']),
        ]);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $response = $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'school_name' => 'Updated School One',
            'school_email' => 'UPDATED@SCHOOL.EXAMPLE.COM',
            'phone' => '9876500011',
            'city' => 'Pune',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'usd',
            'academic_year_start_month' => 6,
            'attendance_start_time' => '08:45',
            'grading_system' => 'letter',
        ]);

        $response->assertRedirect(route('school-settings.edit'));
        $response->assertSessionHas('status', 'School settings updated successfully.');
        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Updated School One',
            'email' => 'updated@school.example.com',
            'phone' => '9876500011',
            'city' => 'Pune',
            'code' => $school->code,
            'status' => School::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('school_settings', [
            'school_id' => $school->id,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'USD',
            'academic_year_start_month' => 6,
            'attendance_start_time' => '08:45',
            'grading_system' => 'letter',
            'settings_json' => json_encode(['week_starts_on' => 'monday']),
        ]);
    }

    public function test_school_admin_cannot_submit_tenant_platform_or_extension_fields(): void
    {
        $school = $this->schoolWithSettings('One');
        $otherSchool = $this->schoolWithSettings('Two');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'school_name' => 'Forged Update',
            'school_id' => $otherSchool->id,
            'logo_path' => 'school-logos/'.$otherSchool->id.'/forged.png',
            'settings_json' => ['arbitrary' => true],
            'code' => 'FORGED',
            'status' => School::STATUS_INACTIVE,
            'deactivated_at' => now()->toDateTimeString(),
            'deactivation_reason' => 'Forged',
            'deleted_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors([
            'school_id',
            'logo_path',
            'settings_json',
            'code',
            'status',
            'deactivated_at',
            'deactivation_reason',
            'deleted_at',
        ]);

        $this->assertDatabaseMissing('schools', [
            'id' => $school->id,
            'name' => 'Forged Update',
        ]);
        $this->assertDatabaseHas('schools', [
            'id' => $otherSchool->id,
            'name' => $otherSchool->name,
        ]);
    }

    public function test_teacher_accountant_and_super_admin_cannot_use_settings_editor(): void
    {
        $school = $this->schoolWithSettings('One');

        foreach ([Role::TEACHER, Role::ACCOUNTANT] as $index => $roleCode) {
            $actor = $this->schoolUser($roleCode, $school, $roleCode.$index.'@example.com');

            $this->actingAs($actor)->get(route('school-settings.edit'))->assertForbidden();
            $this->actingAs($actor)
                ->patch(route('school-settings.update'), $this->validPayload($school))
                ->assertForbidden();
        }

        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin)->get(route('school-settings.edit'))->assertForbidden();
        $this->actingAs($superAdmin)
            ->patch(route('school-settings.update'), $this->validPayload($school))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('schools.show', $school))
            ->assertOk()
            ->assertSee('Default Settings')
            ->assertSee('Asia/Kolkata')
            ->assertDontSee(route('school-settings.edit'), false);
    }

    public function test_settings_navigation_is_visible_only_to_school_admin(): void
    {
        $school = $this->schoolWithSettings('One');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');

        $this->actingAs($schoolAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('school-settings.edit'), false);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('school-settings.edit'), false);

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('school-settings.edit'), false);
    }

    public function test_settings_validation_matches_schema_and_security_rules(): void
    {
        $school = $this->schoolWithSettings('One');
        $otherSchool = $this->schoolWithSettings('Two');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            'school_name' => '',
            'school_email' => $otherSchool->email,
            'country' => '',
            'website' => 'not-a-url',
            'timezone' => 'Invalid/Timezone',
            'currency' => 'RUPEES',
            'academic_year_start_month' => 13,
            'attendance_start_time' => '25:99',
            'grading_system' => 'custom',
        ])->assertSessionHasErrors([
            'school_name',
            'school_email',
            'country',
            'website',
            'timezone',
            'currency',
            'academic_year_start_month',
            'attendance_start_time',
            'grading_system',
        ]);

        $this->assertDatabaseHas('school_settings', [
            'school_id' => $school->id,
            'currency' => 'INR',
        ]);
    }

    public function test_school_logo_upload_uses_generated_tenant_partitioned_path(): void
    {
        Storage::fake('public');
        $school = $this->schoolWithSettings('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $logo = UploadedFile::fake()->image('school-brand.png', 400, 400)->size(250);

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'logo' => $logo,
        ])->assertRedirect(route('school-settings.edit'));

        $path = DB::table('school_settings')->where('school_id', $school->id)->value('logo_path');

        $this->assertIsString($path);
        $this->assertStringStartsWith('school-logos/'.$school->id.'/', $path);
        $this->assertStringNotContainsString('school-brand', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_logo_preview_uses_the_current_request_host_and_port(): void
    {
        $school = $this->schoolWithSettings('One');
        $logoPath = 'school-logos/'.$school->id.'/logo.png';
        DB::table('school_settings')
            ->where('school_id', $school->id)
            ->update(['logo_path' => $logoPath]);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'SERVER_NAME' => '127.0.0.1',
            'SERVER_PORT' => '8000',
        ])->actingAs($admin)
            ->get(route('school-settings.edit'))
            ->assertOk()
            ->assertSee('http://127.0.0.1:8000/storage/'.$logoPath, false);
    }

    public function test_invalid_or_oversized_logo_is_rejected(): void
    {
        Storage::fake('public');
        $school = $this->schoolWithSettings('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'logo' => UploadedFile::fake()->create('not-an-image.jpg', 10, 'text/plain'),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'logo' => UploadedFile::fake()->image('too-large.png')->size(2049),
        ])->assertSessionHasErrors('logo');

        $this->assertNull(DB::table('school_settings')->where('school_id', $school->id)->value('logo_path'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replacing_and_removing_logo_cleans_up_only_current_school_files(): void
    {
        Storage::fake('public');
        $school = $this->schoolWithSettings('One');
        $otherSchool = $this->schoolWithSettings('Two');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $oldPath = 'school-logos/'.$school->id.'/old-logo.png';
        $otherPath = 'school-logos/'.$otherSchool->id.'/other-logo.png';
        Storage::disk('public')->put($oldPath, 'old-logo');
        Storage::disk('public')->put($otherPath, 'other-logo');
        DB::table('school_settings')->where('school_id', $school->id)->update(['logo_path' => $oldPath]);
        DB::table('school_settings')->where('school_id', $otherSchool->id)->update(['logo_path' => $otherPath]);

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'logo' => UploadedFile::fake()->image('replacement.webp')->size(100),
        ])->assertRedirect(route('school-settings.edit'));

        $newPath = DB::table('school_settings')->where('school_id', $school->id)->value('logo_path');
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertExists($otherPath);

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'remove_logo' => '1',
        ])->assertRedirect(route('school-settings.edit'));

        $this->assertNull(DB::table('school_settings')->where('school_id', $school->id)->value('logo_path'));
        Storage::disk('public')->assertMissing($newPath);
        Storage::disk('public')->assertExists($otherPath);
    }

    public function test_logo_upload_and_removal_cannot_be_requested_together(): void
    {
        Storage::fake('public');
        $school = $this->schoolWithSettings('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->patch(route('school-settings.update'), [
            ...$this->validPayload($school),
            'logo' => UploadedFile::fake()->image('replacement.png'),
            'remove_logo' => '1',
        ])->assertSessionHasErrors('logo');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => strtoupper('SCH-'.$key),
            'email' => 'school-'.$key.'@example.com',
            'country' => 'India',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function schoolWithSettings(string $suffix, array $settings = []): School
    {
        $school = $this->school($suffix);

        DB::table('school_settings')->insert([
            'school_id' => $school->id,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'academic_year_start_month' => 4,
            'attendance_start_time' => null,
            'grading_system' => 'percentage',
            'created_at' => now(),
            'updated_at' => now(),
            ...$settings,
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
    private function validPayload(School $school): array
    {
        return [
            'school_name' => $school->name,
            'school_email' => $school->email,
            'phone' => $school->phone,
            'address' => $school->address,
            'city' => $school->city,
            'state' => $school->state,
            'country' => $school->country,
            'postal_code' => $school->postal_code,
            'principal_name' => $school->principal_name,
            'website' => $school->website,
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'academic_year_start_month' => 4,
            'attendance_start_time' => '08:30',
            'grading_system' => 'percentage',
            'remove_logo' => '0',
        ];
    }
}
