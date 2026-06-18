<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_queries_are_scoped_by_school_context(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        Student::factory()->create(['school_id' => $schoolA->id, 'admission_number' => 'ADM-A']);
        Student::factory()->create(['school_id' => $schoolB->id, 'admission_number' => 'ADM-B']);

        TenantContext::set($schoolA->id);

        $students = Student::pluck('admission_number')->all();

        TenantContext::clear();

        $this->assertSame(['ADM-A'], $students);
    }
}
