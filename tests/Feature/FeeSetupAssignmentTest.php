<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\FeeCategoryService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeeSetupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_fee_setup_and_assignment_routes(): void
    {
        $school = $this->school('One');
        $category = $this->feeCategory($school, 'Tuition');
        $structure = $this->feeStructure($school, $category);
        $studentFee = $this->studentFeeAssignment($school, $structure);

        foreach ([
            fn () => $this->get(route('fee-categories.index')),
            fn () => $this->post(route('fee-categories.store'), ['name' => 'Late Fee']),
            fn () => $this->get(route('fee-categories.show', $category)),
            fn () => $this->patch(route('fee-categories.deactivate', $category)),
            fn () => $this->get(route('fee-structures.index')),
            fn () => $this->post(route('fee-structures.store'), []),
            fn () => $this->get(route('fee-structures.show', $structure)),
            fn () => $this->get(route('student-fees.index')),
            fn () => $this->post(route('student-fees.store'), []),
            fn () => $this->get(route('student-fees.show', $studentFee)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_accountant_and_teacher_are_denied_fee_setup_and_assignment(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $superAdmin = $this->superAdmin();
        $this->actingAs($admin)->post(route('fee-categories.store'), [
            'name' => 'Tuition',
            'description' => 'Annual tuition',
        ])->assertRedirect();
        $category = FeeCategory::query()->withoutGlobalScopes()->where('name', 'Tuition')->firstOrFail();
        $structure = $this->feeStructure($school, $category);

        $this->actingAs($superAdmin)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('fee-categories.store'), ['name' => 'Other'])->assertForbidden();
        $this->actingAs($accountant)->get(route('fee-structures.create'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student-fees.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('student-fees.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('student-fees.store'), [
            'fee_structure_id' => $structure->id,
            'student_id' => $this->eligibleStudent($school, $structure)->id,
        ])->assertForbidden();
    }

    public function test_school_admin_manages_fee_categories_structures_and_assignments_with_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');

        $this->actingAs($admin)->post(route('fee-categories.store'), [
            'name' => '  Tuition  ',
            'description' => 'Annual tuition',
            'school_id' => 999,
            'status' => 'inactive',
        ])->assertSessionHasErrors(['school_id', 'status']);

        $response = $this->actingAs($admin)->post(route('fee-categories.store'), [
            'name' => '  Tuition  ',
            'description' => 'Annual tuition',
        ]);
        $categoryId = (int) DB::table('fee_categories')->where('name', 'Tuition')->value('id');
        $response->assertRedirect(route('fee-categories.show', $categoryId));

        $structure = $this->structure($school, 'A');
        $createStructure = $this->actingAs($admin)->post(route('fee-structures.store'), [
            'fee_category_id' => $categoryId,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'amount' => '1500.00',
            'due_date' => '2026-08-01',
            'frequency' => FeeStructure::FREQUENCY_ANNUAL,
        ]);
        $structureId = (int) DB::table('fee_structures')->value('id');
        $createStructure->assertRedirect(route('fee-structures.show', $structureId));

        $student = $this->eligibleStudent($school, FeeStructure::query()->withoutGlobalScopes()->findOrFail($structureId));

        $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structureId,
            'student_id' => $student->id,
            'discount_amount' => '100.00',
            'amount' => '999.00',
            'payable_amount' => '999.00',
        ])->assertSessionHasErrors(['amount', 'payable_amount']);

        $assign = $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structureId,
            'student_id' => $student->id,
            'discount_amount' => '100.00',
        ]);
        $studentFeeId = (int) DB::table('student_fees')->value('id');
        $assign->assertRedirect(route('student-fees.show', $studentFeeId));

        $this->assertDatabaseHas('student_fees', [
            'id' => $studentFeeId,
            'school_id' => $school->id,
            'student_id' => $student->id,
            'fee_structure_id' => $structureId,
            'amount' => '1500.00',
            'discount_amount' => '100.00',
            'payable_amount' => '1400.00',
            'paid_amount' => '0.00',
            'balance_amount' => '1400.00',
            'status' => StudentFee::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'module' => 'fee_management',
            'action' => 'assigned',
            'subject_type' => StudentFee::class,
            'subject_id' => $studentFeeId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'auditable_type' => StudentFee::class,
            'auditable_id' => $studentFeeId,
            'event' => 'assigned',
        ]);
    }

    public function test_assignment_rejects_ineligible_students_discount_overflow_and_duplicates(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $category = $this->feeCategory($school, 'Tuition');
        $structure = $this->feeStructure($school, $category);
        $eligible = $this->eligibleStudent($school, $structure);
        $otherStructure = $this->structure($school, 'B');
        $otherStudent = $this->student($school, 'OTHER');
        $this->enrollment($school, $otherStudent, $otherStructure, 'ROLL-B');

        $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structure->id,
            'student_id' => $otherStudent->id,
        ])->assertSessionHasErrors(['student_id']);

        $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structure->id,
            'student_id' => $eligible->id,
            'discount_amount' => '2000.00',
        ])->assertSessionHasErrors(['discount_amount']);

        $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structure->id,
            'student_id' => $eligible->id,
            'discount_amount' => '0.00',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('student-fees.store'), [
            'fee_structure_id' => $structure->id,
            'student_id' => $eligible->id,
        ])->assertSessionHasErrors(['student_id']);
    }

    public function test_cross_tenant_fee_records_are_not_exposed_or_mutable(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $categoryA = $this->feeCategory($schoolA, 'Tuition A');
        $structureA = $this->feeStructure($schoolA, $categoryA);
        $studentFeeA = $this->studentFeeAssignment($schoolA, $structureA);

        $this->actingAs($adminB)->get(route('fee-categories.show', $categoryA))->assertNotFound();
        $this->actingAs($adminB)->put(route('fee-categories.update', $categoryA), ['name' => 'Hacked'])->assertNotFound();
        $this->actingAs($adminB)->get(route('fee-structures.show', $structureA))->assertNotFound();
        $this->actingAs($adminB)->get(route('student-fees.show', $studentFeeA))->assertNotFound();
    }

    public function test_direct_services_reject_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $category = $this->feeCategory($school, 'Tuition');

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(FeeCategoryService::class)->create([
            'name' => 'Invalid',
        ], $admin));
    }

    public function test_fee_setup_routes_match_contract_without_destroy(): void
    {
        $this->assertNull(collect(app('router')->getRoutes())->first(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'fee-categories')));
        $this->assertNull(collect(app('router')->getRoutes())->first(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'fee-structures')));
        $this->assertNull(collect(app('router')->getRoutes())->first(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'student-fees')));
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'FEE-SETUP-'.$suffix,
            'email' => strtolower('fee-setup-'.$suffix).'@school.example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'name' => str($roleCode)->replace('_', ' ')->title()->toString(),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()
            ->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))
            ->firstOrFail();
    }

    private function feeCategory(School $school, string $name): FeeCategory
    {
        return $this->tenant($school, fn () => FeeCategory::create([
            'name' => $name,
            'description' => $name.' description',
            'status' => FeeCategory::STATUS_ACTIVE,
        ]));
    }

    /**
     * @return array{year: AcademicYear, class: SchoolClass, section: Section}
     */
    private function structure(School $school, string $suffix): array
    {
        return $this->tenant($school, function () use ($suffix): array {
            $year = AcademicYear::create([
                'name' => '2026-'.$suffix,
                'start_date' => now()->subMonths(2)->toDateString(),
                'end_date' => now()->addMonths(8)->toDateString(),
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'sort_order' => 1,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $class->id,
                'name' => 'Section '.$suffix,
                'capacity' => 30,
                'status' => Section::STATUS_ACTIVE,
            ]);

            return compact('year', 'class', 'section');
        });
    }

    private function feeStructure(School $school, FeeCategory $category): FeeStructure
    {
        $structure = $this->structure($school, $category->name);

        return $this->tenant($school, fn () => FeeStructure::create([
            'fee_category_id' => $category->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'amount' => '1500.00',
            'due_date' => '2026-08-01',
            'frequency' => FeeStructure::FREQUENCY_ANNUAL,
            'status' => FeeStructure::STATUS_ACTIVE,
        ]));
    }

    private function student(School $school, string $suffix): Student
    {
        return $this->tenant($school, fn () => Student::create([
            'admission_no' => 'ADM-'.$suffix,
            'first_name' => 'Student',
            'last_name' => $suffix,
            'gender' => Student::GENDER_FEMALE,
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '9876500001',
            'guardian_email' => strtolower('guardian-'.$suffix).'@example.test',
            'admission_date' => now()->subMonth()->toDateString(),
            'status' => Student::STATUS_ACTIVE,
        ]));
    }

    private function enrollment(
        School $school,
        Student $student,
        array $structure,
        string $rollNo,
    ): StudentEnrollment {
        return $this->tenant($school, fn () => StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $structure['section']->id,
            'roll_no' => $rollNo,
            'enrollment_date' => now()->toDateString(),
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]));
    }

    private function eligibleStudent(School $school, FeeStructure $structure): Student
    {
        return $this->tenant($school, function () use ($structure): Student {
            $structure = FeeStructure::query()->with(['academicYear', 'schoolClass'])->findOrFail($structure->id);
            $section = Section::query()->where('class_id', $structure->class_id)->firstOrFail();
            $student = Student::create([
                'admission_no' => 'ADM-ELIG-'.$structure->id,
                'first_name' => 'Student',
                'last_name' => 'Eligible',
                'gender' => Student::GENDER_FEMALE,
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'guardian_name' => 'Guardian Eligible',
                'guardian_phone' => '9876500001',
                'guardian_email' => 'guardian-eligible@example.test',
                'admission_date' => now()->subMonth()->toDateString(),
                'status' => Student::STATUS_ACTIVE,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $structure->academic_year_id,
                'class_id' => $structure->class_id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-'.$student->id,
                'enrollment_date' => now()->toDateString(),
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);

            return $student;
        });
    }

    private function studentFeeAssignment(School $school, FeeStructure $structure): StudentFee
    {
        $student = $this->eligibleStudent($school, $structure);

        return $this->tenant($school, fn () => StudentFee::create([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'academic_year_id' => $structure->academic_year_id,
            'amount' => '1500.00',
            'discount_amount' => '0.00',
            'payable_amount' => '1500.00',
            'paid_amount' => '0.00',
            'balance_amount' => '1500.00',
            'status' => StudentFee::STATUS_PENDING,
        ]));
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
