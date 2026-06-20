<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_state_changing_web_requests_reject_missing_csrf_tokens(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $this->post('/login', [
                'email' => 'superadmin@example.com',
                'password' => 'password',
            ])->assertStatus(419);
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }
    }

    public function test_school_output_is_blade_escaped(): void
    {
        $school = School::create([
            'name' => '<script>alert(1)</script>',
            'code' => 'XSS-001',
            'email' => 'xss-school@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('schools.show', $school))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_school_search_treats_sql_injection_payload_as_bound_text(): void
    {
        $visibleOnlyWithoutInjection = School::create([
            'name' => 'Green Valley School',
            'code' => 'SQL-001',
            'email' => 'green@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
        $secondSchool = School::create([
            'name' => 'Blue Ridge School',
            'code' => 'SQL-002',
            'email' => 'blue@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('schools.index', ['search' => "%' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee($visibleOnlyWithoutInjection->name)
            ->assertDontSee($secondSchool->name);
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }
}
