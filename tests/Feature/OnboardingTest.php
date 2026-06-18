<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_onboarding_creates_school_and_admin_user(): void
    {
        $response = $this->post('/onboarding', [
            'school_name' => 'Northwind Academy',
            'school_slug' => 'northwind-academy',
            'admin_name' => 'Admin User',
            'admin_email' => 'admin@northwind.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $school = School::first();

        $response->assertRedirect(route('dashboard', $school));
        $this->assertDatabaseHas('schools', ['slug' => 'northwind-academy']);
        $this->assertDatabaseHas('users', [
            'school_id' => $school->id,
            'email' => 'admin@northwind.test',
            'role' => 'school_admin',
        ]);
    }
}
