<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
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
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
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

    public function test_guests_are_redirected_from_analytics_route(): void
    {
        $this->get(route('analytics.index'))->assertRedirect(route('login'));
    }

    public function test_teacher_and_accountant_cannot_open_analytics_page(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $this->tenant($school, function () use ($teacher): void {
            Teacher::create([
                'user_id' => $teacher->id,
                'employee_code' => 'TCH-ONE',
                'joining_date' => '2025-04-01',
                'status' => Teacher::STATUS_ACTIVE,
            ]);
        });
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $this->actingAs($teacher)->get(route('analytics.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('analytics.index'))->assertForbidden();
    }

    public function test_super_admin_sees_platform_analytics_with_chart_payload(): void
    {
        $structure = $this->tenant($this->school('PLT'), fn (): array => $this->seedSchoolMetrics('PLT'));

        $response = $this->actingAs($this->superAdmin())->get(route('analytics.index'));

        $response->assertOk()
            ->assertSee('Platform analytics')
            ->assertSee('Schools by status')
            ->assertSee('analytics-chart-0', false)
            ->assertSee('data-charts', false);

        $charts = $response->viewData('charts');
        $this->assertCount(4, $charts);
        $this->assertSame(1, $charts[0]['datasets'][0]['data'][0]);
        $this->assertContains($structure['school']->name, $charts[1]['labels']);
    }

    public function test_school_admin_sees_school_analytics_matching_database_values(): void
    {
        $structure = $this->tenant($this->school('ADM'), fn (): array => $this->seedSchoolMetrics('ADM'));
        $admin = $structure['admin'];

        $response = $this->actingAs($admin)->get(route('analytics.index'));

        $response->assertOk()
            ->assertSee('School analytics')
            ->assertSee('Students by status')
            ->assertSee('Examination results');

        $charts = $response->viewData('charts');
        $this->assertSame(1, $charts[0]['datasets'][0]['data'][0]);
        $this->assertSame(1, $charts[2]['datasets'][0]['data'][0]);
        $this->assertSame(1, $charts[3]['datasets'][0]['data'][0]);
    }

    public function test_analytics_service_denies_cross_tenant_access(): void
    {
        $school = $this->school('Cross');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'cross-admin@example.com');
        $otherSchool = $this->school('Other');

        $this->tenant($otherSchool, function () use ($admin): void {
            $this->expectException(AuthorizationException::class);

            app(AnalyticsService::class)->pageFor($admin);
        });
    }

    public function test_teacher_dashboard_shows_embedded_widgets_without_analytics_navigation(): void
    {
        $school = $this->school('TEA');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $this->tenant($school, function () use ($teacher): void {
            Teacher::create([
                'user_id' => $teacher->id,
                'employee_code' => 'TCH-TEA',
                'joining_date' => '2025-04-01',
                'status' => Teacher::STATUS_ACTIVE,
            ]);
        });

        $this->actingAs($teacher)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assigned sections')
            ->assertDontSee(route('analytics.index'), false);
    }

    /**
     * @return array<string, mixed>
     */
    private function seedSchoolMetrics(string $suffix): array
    {
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
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_code' => 'TCH-'.$suffix,
            'joining_date' => '2025-04-01',
            'status' => Teacher::STATUS_ACTIVE,
        ]);
        $section = Section::create([
            'class_id' => $schoolClass->id,
            'name' => 'Section '.$suffix,
            'code' => 'SEC-'.$suffix,
            'teacher_id' => $teacher->id,
            'status' => Section::STATUS_ACTIVE,
        ]);
        $subject = Subject::create([
            'class_id' => $schoolClass->id,
            'teacher_id' => $teacher->id,
            'name' => 'Mathematics '.$suffix,
            'code' => 'MATH-'.$suffix,
            'status' => Subject::STATUS_ACTIVE,
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
        Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-06-20',
            'status' => Attendance::STATUS_PRESENT,
            'marked_by' => $teacherUser->id,
        ]);
        $category = FeeCategory::create([
            'name' => 'Tuition',
            'description' => 'Annual tuition',
            'status' => FeeCategory::STATUS_ACTIVE,
        ]);
        $feeStructure = FeeStructure::create([
            'fee_category_id' => $category->id,
            'academic_year_id' => $year->id,
            'class_id' => $schoolClass->id,
            'amount' => '1000.00',
            'status' => FeeStructure::STATUS_ACTIVE,
        ]);
        StudentFee::create([
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'academic_year_id' => $year->id,
            'amount' => '1000.00',
            'discount_amount' => '0.00',
            'payable_amount' => '1000.00',
            'paid_amount' => '0.00',
            'balance_amount' => '1000.00',
            'due_date' => '2026-08-01',
            'status' => StudentFee::STATUS_PENDING,
        ]);
        $exam = Exam::create([
            'academic_year_id' => $year->id,
            'name' => 'Mid Term '.$suffix,
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'status' => Exam::STATUS_ONGOING,
        ]);
        $examSubject = ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => $subject->id,
            'class_id' => $schoolClass->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ]);
        $admin = User::factory()->create([
            'school_id' => app(TenantContext::class)->schoolId(),
            'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
            'email' => strtolower("admin-{$suffix}@example.com"),
        ]);
        ExamResult::create([
            'exam_id' => $exam->id,
            'exam_subject_id' => $examSubject->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'marks_obtained' => '85.00',
            'grade_scale_id' => null,
            'result_status' => ExamResult::STATUS_PASS,
            'entered_by' => $admin->id,
        ]);

        return [
            'school' => School::query()->findOrFail(app(TenantContext::class)->schoolId()),
            'admin' => $admin,
        ];
    }

    private function school(string $suffix): School
    {
        $key = strtolower($suffix);

        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ANL-'.$key,
            'email' => 'analytics-'.$key.'@example.com',
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
