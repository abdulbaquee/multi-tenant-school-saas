<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SchoolSettingIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Route::middleware(['web', 'auth', 'tenant.context'])
            ->get('/_test/school-settings', function () {
                return response()->json([
                    'school_ids' => SchoolSetting::query()
                        ->orderBy('id')
                        ->pluck('school_id')
                        ->all(),
                ]);
            });

        Route::middleware(['web', 'auth', 'tenant.context'])
            ->get('/_test/school-settings/{schoolSetting}', function (SchoolSetting $schoolSetting) {
                return response()->json([
                    'id' => $schoolSetting->id,
                    'school_id' => $schoolSetting->school_id,
                ]);
            });

        Route::middleware(['web', 'auth', 'tenant.context'])
            ->post('/_test/school-settings', function (Request $request) {
                $setting = new SchoolSetting([
                    'timezone' => 'Asia/Kolkata',
                    'currency' => 'INR',
                    'academic_year_start_month' => 4,
                    'grading_system' => 'percentage',
                ]);
                $setting->school_id = $request->integer('school_id');
                $setting->save();

                return response()->json(['id' => $setting->id], 201);
            });
    }

    public function test_unresolved_context_denies_reads_and_creates(): void
    {
        $school = $this->school('One');
        $this->createSetting($school);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
        $this->assertSame(0, SchoolSetting::query()->count());

        try {
            SchoolSetting::create();
            $this->fail('A strict tenant-owned record was created without tenant context.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('Tenant context is required', $exception->getMessage());
        }

        $this->assertDatabaseCount('school_settings', 1);
    }

    public function test_scope_resolves_current_tenant_for_every_query(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $settingOne = $this->createSetting($schoolOne);
        $settingTwo = $this->createSetting($schoolTwo);
        $context = app(TenantContext::class);

        $context->setTenant($schoolOne->id);
        $this->assertSame([$settingOne->id], SchoolSetting::query()->pluck('id')->all());

        $context->setTenant($schoolTwo->id);
        $this->assertSame([$settingTwo->id], SchoolSetting::query()->pluck('id')->all());

        $context->setPlatform();
        $this->assertEqualsCanonicalizing(
            [$settingOne->id, $settingTwo->id],
            SchoolSetting::query()->pluck('id')->all(),
        );

        $context->clear();
        $this->assertSame(0, SchoolSetting::query()->count());
    }

    public function test_tenant_create_overwrites_a_forged_school_id(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');

        $this->actingAs($schoolAdmin)
            ->postJson('/_test/school-settings', ['school_id' => $schoolTwo->id])
            ->assertCreated();

        $this->assertDatabaseHas('school_settings', [
            'school_id' => $schoolOne->id,
        ]);
        $this->assertDatabaseMissing('school_settings', [
            'school_id' => $schoolTwo->id,
        ]);
    }

    public function test_platform_context_can_read_all_settings_but_cannot_create_one(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $this->createSetting($schoolOne);
        $this->createSetting($schoolTwo);

        $this->actingAs($this->superAdmin())
            ->getJson('/_test/school-settings')
            ->assertOk()
            ->assertJson([
                'school_ids' => [$schoolOne->id, $schoolTwo->id],
            ]);

        $context = app(TenantContext::class);
        $context->setPlatform();

        try {
            SchoolSetting::create();
            $this->fail('Platform context created a strict tenant-owned record directly.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('Tenant context is required', $exception->getMessage());
        } finally {
            $context->clear();
        }
    }

    public function test_tenant_ownership_is_immutable(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $setting = $this->createSetting($schoolOne);
        $context = app(TenantContext::class);
        $context->setTenant($schoolOne->id);

        try {
            $setting = SchoolSetting::query()->findOrFail($setting->id);
            $setting->school_id = $schoolTwo->id;
            $setting->save();
            $this->fail('Tenant ownership was changed after creation.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
        } finally {
            $context->clear();
        }

        $this->assertDatabaseHas('school_settings', [
            'id' => $setting->id,
            'school_id' => $schoolOne->id,
        ]);
    }

    public function test_cross_tenant_route_model_binding_returns_not_found(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $schoolAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin@example.com');
        $settingOne = $this->createSetting($schoolOne);
        $settingTwo = $this->createSetting($schoolTwo);

        $this->actingAs($schoolAdmin)
            ->getJson('/_test/school-settings/'.$settingOne->id)
            ->assertOk()
            ->assertJson(['school_id' => $schoolOne->id]);

        $this->actingAs($schoolAdmin)
            ->getJson('/_test/school-settings/'.$settingTwo->id)
            ->assertNotFound();
    }

    public function test_sequential_school_requests_never_share_settings(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $userOne = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolOne, 'admin1@example.com');
        $userTwo = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolTwo, 'admin2@example.com');
        $this->createSetting($schoolOne);
        $this->createSetting($schoolTwo);

        $this->actingAs($userOne)
            ->getJson('/_test/school-settings')
            ->assertJson(['school_ids' => [$schoolOne->id]]);

        $this->actingAs($userTwo)
            ->getJson('/_test/school-settings')
            ->assertJson(['school_ids' => [$schoolTwo->id]]);

        $this->assertTrue(app(TenantContext::class)->isUnresolved());
    }

    public function test_school_setting_defaults_relationship_and_one_to_one_constraint(): void
    {
        $school = $this->school('One');
        $setting = $this->createSetting($school, [
            'settings_json' => ['week_starts_on' => 'monday'],
        ]);
        $context = app(TenantContext::class);
        $context->setTenant($school->id);

        $setting = $setting->fresh();
        $this->assertSame('Asia/Kolkata', $setting->timezone);
        $this->assertSame('INR', $setting->currency);
        $this->assertSame(4, $setting->academic_year_start_month);
        $this->assertSame('percentage', $setting->grading_system);
        $this->assertSame(['week_starts_on' => 'monday'], $setting->settings_json);
        $this->assertTrue($school->settings()->first()->is($setting));

        try {
            SchoolSetting::create();
            $this->fail('A second School Settings row was created for one school.');
        } catch (QueryException) {
            $this->assertDatabaseCount('school_settings', 1);
        } finally {
            $context->clear();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSetting(School $school, array $attributes = []): SchoolSetting
    {
        $context = app(TenantContext::class);
        $context->setTenant($school->id);

        try {
            return SchoolSetting::create($attributes);
        } finally {
            $context->clear();
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
