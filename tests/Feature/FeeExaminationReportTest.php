<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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
use App\Services\FeeReportService;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeExaminationReportTest extends TestCase
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

    public function test_guests_are_redirected_from_fee_and_examination_report_routes(): void
    {
        foreach ([
            route('reports.fees.index'),
            route('reports.examinations.index'),
            route('reports.fees.export'),
            route('reports.examinations.export'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_teacher_can_open_examination_report_but_not_fee_report(): void
    {
        $structure = $this->examinationStructure('TEA');
        $teacherUser = $structure['teacherUser'];

        $this->actingAs($teacherUser)->get(route('reports.examinations.index'))->assertOk()->assertSee('ADM-TEA-1');
        $this->actingAs($teacherUser)->get(route('reports.fees.index'))->assertForbidden();
    }

    public function test_accountant_can_open_fee_report_but_not_examination_report(): void
    {
        $structure = $this->feeStructure('ACC');
        $accountant = $structure['accountant'];

        $this->actingAs($accountant)->get(route('reports.fees.index'))->assertOk()->assertSee('ADM-ACC-1');
        $this->actingAs($accountant)->get(route('reports.examinations.index'))->assertForbidden();
    }

    public function test_teacher_sees_assigned_examination_results_only(): void
    {
        $structure = $this->examinationStructure('SCOPE', withPeerSubject: true);
        $teacherUser = $structure['teacherUser'];

        $this->actingAs($teacherUser)->get(route('reports.examinations.index'))->assertOk()
            ->assertSee('Mathematics SCOPE')
            ->assertDontSee('Science SCOPE');
    }

    public function test_school_admin_fee_report_excludes_guardian_details_and_supports_csv_export(): void
    {
        $structure = $this->feeStructure('ADM');
        $admin = $structure['admin'];

        $this->actingAs($admin)->get(route('reports.fees.index'))->assertOk()
            ->assertSee('ADM-ADM-1')
            ->assertSee('Tuition')
            ->assertDontSee('Guardian ADM-1')
            ->assertDontSee('9876500000');

        $response = $this->actingAs($admin)->get(route('reports.fees.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ADM-ADM-1', $response->streamedContent());
    }

    public function test_school_admin_examination_report_excludes_guardian_details_and_supports_csv_export(): void
    {
        $structure = $this->examinationStructure('EXM');
        $admin = $structure['admin'];

        $this->actingAs($admin)->get(route('reports.examinations.index'))->assertOk()
            ->assertSee('ADM-EXM-1')
            ->assertSee('Mid Term EXM')
            ->assertDontSee('Guardian EXM-1')
            ->assertDontSee('9876500000');

        $response = $this->actingAs($admin)->get(route('reports.examinations.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ADM-EXM-1', $response->streamedContent());
    }

    public function test_super_admin_fee_and_examination_reports_show_platform_summaries_only(): void
    {
        $feeStructure = $this->feeStructure('FEE');
        $examStructure = $this->examinationStructure('EXA');

        $this->actingAs($this->superAdmin())->get(route('reports.fees.index'))->assertOk()
            ->assertSee('School FEE')
            ->assertDontSee('Guardian FEE-1');

        $this->actingAs($this->superAdmin())->get(route('reports.examinations.index'))->assertOk()
            ->assertSee('School EXA')
            ->assertDontSee('ADM-EXA-1');
    }

    public function test_fee_report_rejects_forged_scope_filters(): void
    {
        $structure = $this->feeStructure('FORGE');
        $admin = $structure['admin'];

        $this->actingAs($admin)
            ->get(route('reports.fees.index', ['school_id' => $structure['school']->id]))
            ->assertSessionHasErrors('school_id');
    }

    public function test_fee_report_service_denies_cross_tenant_access(): void
    {
        $school = $this->school('Cross');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'cross-admin@example.com');
        $otherSchool = $this->school('Other');

        $this->tenant($otherSchool, function () use ($admin): void {
            $this->expectException(AuthorizationException::class);

            app(FeeReportService::class)->reportFor($admin, []);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function feeStructure(string $suffix): array
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
                'section_id' => Section::create([
                    'class_id' => $schoolClass->id,
                    'name' => 'Section '.$suffix,
                    'code' => 'SEC-'.$suffix,
                    'status' => Section::STATUS_ACTIVE,
                ])->id,
                'roll_no' => 'ROLL-'.$suffix.'-1',
                'enrollment_date' => '2026-04-02',
                'status' => StudentEnrollment::STATUS_ACTIVE,
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
                'student' => $student,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function examinationStructure(string $suffix, bool $withPeerSubject = false): array
    {
        return $this->tenant($this->school($suffix), function () use ($suffix, $withPeerSubject): array {
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
            $subject = Subject::create([
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacherProfile->id,
                'name' => 'Mathematics '.$suffix,
                'code' => 'MATH-'.$suffix,
                'status' => Subject::STATUS_ACTIVE,
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

            if ($withPeerSubject) {
                $peerTeacherUser = User::factory()->create([
                    'school_id' => app(TenantContext::class)->schoolId(),
                    'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                    'email' => strtolower("peer-teacher-{$suffix}@example.com"),
                ]);
                $peerTeacher = Teacher::create([
                    'user_id' => $peerTeacherUser->id,
                    'employee_code' => 'TCH-PEER-'.$suffix,
                    'joining_date' => '2025-04-01',
                    'status' => Teacher::STATUS_ACTIVE,
                ]);
                $peerSubject = Subject::create([
                    'class_id' => $schoolClass->id,
                    'teacher_id' => $peerTeacher->id,
                    'name' => 'Science '.$suffix,
                    'code' => 'SCI-'.$suffix,
                    'status' => Subject::STATUS_ACTIVE,
                ]);
                $peerExamSubject = ExamSubject::create([
                    'exam_id' => $exam->id,
                    'subject_id' => $peerSubject->id,
                    'class_id' => $schoolClass->id,
                    'max_marks' => '100.00',
                    'passing_marks' => '33.00',
                ]);
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'exam_subject_id' => $peerExamSubject->id,
                    'student_id' => $student->id,
                    'subject_id' => $peerSubject->id,
                    'marks_obtained' => '72.00',
                    'grade_scale_id' => null,
                    'result_status' => ExamResult::STATUS_PASS,
                    'entered_by' => $admin->id,
                ]);
            }

            return [
                'school' => School::query()->findOrFail(app(TenantContext::class)->schoolId()),
                'admin' => $admin,
                'teacherUser' => $teacherUser,
                'teacherProfile' => $teacherProfile,
                'student' => $student,
            ];
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
