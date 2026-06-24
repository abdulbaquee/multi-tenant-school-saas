<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StudentReportService;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-22 08:00:00 Asia/Kolkata');
        $this->seed();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_from_student_and_attendance_report_routes(): void
    {
        foreach ([
            route('reports.students.index'),
            route('reports.attendance.index'),
            route('reports.students.export'),
            route('reports.attendance.export'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_accountant_can_open_fee_context_student_report_but_not_attendance_report(): void
    {
        $structure = $this->structure('ACC');
        $accountant = $structure['accountant'];
        $this->assignStudentFee($structure);

        $this->actingAs($accountant)->get(route('reports.students.index'))->assertOk()->assertSee('ADM-ACC-1');
        $this->actingAs($accountant)->get(route('reports.attendance.index'))->assertForbidden();
    }

    public function test_teacher_sees_assigned_student_and_attendance_reports_only(): void
    {
        $structure = $this->structure('TEA');
        $teacherUser = $structure['teacherUser'];
        $this->saveAttendance($structure);

        $this->actingAs($teacherUser)->get(route('reports.students.index'))->assertOk()->assertSee('ADM-TEA-1');
        $this->actingAs($teacherUser)->get(route('reports.attendance.index'))->assertOk()->assertSee('ADM-TEA-1');

        $other = $this->structure('OTHER');
        $this->saveAttendance($other);

        $this->actingAs($teacherUser)->get(route('reports.students.index'))->assertOk()->assertDontSee('ADM-OTHER-1');
        $this->actingAs($teacherUser)->get(route('reports.attendance.index'))->assertOk()->assertDontSee('ADM-OTHER-1');
    }

    public function test_school_admin_student_report_excludes_guardian_details_and_supports_csv_export(): void
    {
        $structure = $this->structure('ADM');
        $admin = $structure['admin'];

        $this->actingAs($admin)->get(route('reports.students.index'))->assertOk()
            ->assertSee('ADM-ADM-1')
            ->assertDontSee('Guardian ADM-1')
            ->assertDontSee('9876500000');

        $response = $this->actingAs($admin)->get(route('reports.students.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ADM-ADM-1', $response->streamedContent());
    }

    public function test_super_admin_student_and_attendance_reports_show_platform_summaries_only(): void
    {
        $structure = $this->structure('SHA');
        $this->saveAttendance($structure);

        $this->actingAs($this->superAdmin())->get(route('reports.students.index'))->assertOk()
            ->assertSee('School SHA')
            ->assertDontSee('Guardian SHA-1');

        $this->actingAs($this->superAdmin())->get(route('reports.attendance.index'))->assertOk()
            ->assertSee('School SHA')
            ->assertDontSee('ADM-SHA-1');
    }

    public function test_student_report_rejects_forged_scope_filters(): void
    {
        $structure = $this->structure('FORGE');
        $admin = $structure['admin'];

        $this->actingAs($admin)
            ->get(route('reports.students.index', ['school_id' => $structure['school']->id]))
            ->assertSessionHasErrors('school_id');
    }

    public function test_student_report_service_denies_cross_tenant_access(): void
    {
        $school = $this->school('Cross');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'cross-admin@example.com');
        $otherSchool = $this->school('Other');

        $this->tenant($otherSchool, function () use ($admin): void {
            $this->expectException(AuthorizationException::class);

            app(StudentReportService::class)->reportFor($admin, []);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function structure(string $suffix): array
    {
        return $this->tenant($this->school($suffix), function () use ($suffix): array {
            SchoolSetting::create([]);
            $year = AcademicYear::create([
                'name' => 'Year '.$suffix,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $schoolClass = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $teacherUser = User::factory()->create([
                'school_id' => app(TenantContext::class)->schoolId(),
                'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                'email' => strtolower("teacher-{$suffix}@example.com"),
            ]);
            $teacherProfile = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'TCH-'.$suffix,
                'joining_date' => '2025-04-01',
                'status' => Teacher::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $schoolClass->id,
                'name' => 'Section '.$suffix,
                'code' => 'SEC-'.$suffix,
                'teacher_id' => $teacherProfile->id,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $student = Student::create([
                'admission_no' => 'ADM-'.$suffix.'-1',
                'first_name' => 'Student',
                'last_name' => $suffix.'-1',
                'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
                'date_of_birth' => '2015-05-10',
                'guardian_name' => 'Guardian '.$suffix.'-1',
                'guardian_phone' => '9876500000',
                'admission_date' => '2026-04-02',
                'status' => Student::STATUS_ACTIVE,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $schoolClass->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-'.$suffix.'-1',
                'enrollment_date' => '2026-04-02',
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);

            return [
                'school' => School::query()->findOrFail(app(TenantContext::class)->schoolId()),
                'admin' => User::factory()->create([
                    'school_id' => app(TenantContext::class)->schoolId(),
                    'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
                    'email' => strtolower("admin-{$suffix}@example.com"),
                ]),
                'accountant' => User::factory()->create([
                    'school_id' => app(TenantContext::class)->schoolId(),
                    'role_id' => Role::query()->where('code', Role::ACCOUNTANT)->value('id'),
                    'email' => strtolower("accountant-{$suffix}@example.com"),
                ]),
                'teacherUser' => $teacherUser,
                'teacherProfile' => $teacherProfile,
                'year' => $year,
                'class' => $schoolClass,
                'section' => $section,
                'student' => $student,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function assignStudentFee(array $structure): void
    {
        $this->tenant($structure['school'], function () use ($structure): void {
            $category = FeeCategory::create([
                'name' => 'Tuition',
                'description' => 'Annual tuition',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
            $feeStructure = FeeStructure::create([
                'fee_category_id' => $category->id,
                'academic_year_id' => $structure['year']->id,
                'class_id' => $structure['class']->id,
                'amount' => '1000.00',
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);
            StudentFee::create([
                'student_id' => $structure['student']->id,
                'fee_structure_id' => $feeStructure->id,
                'academic_year_id' => $structure['year']->id,
                'amount' => '1000.00',
                'discount_amount' => '0.00',
                'payable_amount' => '1000.00',
                'paid_amount' => '0.00',
                'balance_amount' => '1000.00',
                'due_date' => '2026-08-01',
                'status' => StudentFee::STATUS_PENDING,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $structure
     */
    private function saveAttendance(array $structure): void
    {
        $this->tenant($structure['school'], function () use ($structure): void {
            app(AttendanceService::class)->saveRoster([
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
                'mode' => 'roster',
                'entries' => [[
                    'student_id' => $structure['student']->id,
                    'status' => Attendance::STATUS_PRESENT,
                    'remarks' => null,
                ]],
            ], $structure['admin'] ?? $structure['teacherUser']);
        });
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'REP-'.$key,
            'email' => 'reports-'.$key.'@example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role_id' => (int) Role::query()->where('code', $roleCode)->value('id'),
            'email' => $email,
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant((int) $school->id, $callback);
    }
}
