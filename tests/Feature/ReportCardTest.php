<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeScaleService;
use App\Services\ReportCardService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_report_card_routes(): void
    {
        $school = $this->school('One');
        $graph = $this->reportGraph($school, 'A', withResults: true);
        $reportCard = $this->tenant($school, fn () => $this->generateReportCard($graph));

        foreach ([
            fn () => $this->get(route('report-cards.index')),
            fn () => $this->get(route('report-cards.show', $reportCard)),
            fn () => $this->get(route('report-cards.print', $reportCard)),
            fn () => $this->post(route('report-cards.generate', $graph['exam'])),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_and_accountant_are_denied_report_cards(): void
    {
        $school = $this->school('One');
        $graph = $this->reportGraph($school, 'A');
        $superAdmin = $this->superAdmin();
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $this->actingAs($superAdmin)->get(route('report-cards.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('report-cards.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('report-cards.generate', $graph['exam']))->assertForbidden();
    }

    public function test_school_admin_generates_views_and_prints_report_cards(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->reportGraph($school, 'A', studentCount: 2, withResults: true);

        $this->actingAs($admin)->post(route('report-cards.generate', $graph['exam']))->assertRedirect();
        $this->assertDatabaseCount('report_cards', 2);
        $this->assertDatabaseHas('report_cards', [
            'student_id' => $graph['students'][0]->id,
            'marks_obtained' => '85.00',
            'total_marks' => '100.00',
            'result_status' => ReportCard::STATUS_PASS,
            'generated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'examination_management',
            'action' => 'generated',
            'subject_type' => ReportCard::class,
        ]);

        $reportCardId = (int) DB::table('report_cards')->where('student_id', $graph['students'][0]->id)->value('id');
        $this->actingAs($admin)->get(route('report-cards.index'))->assertOk()->assertSee('ADM-A-1');
        $this->actingAs($admin)->get(route('report-cards.show', $reportCardId))->assertOk()->assertSee('Print Report Card');
        $this->actingAs($admin)->get(route('report-cards.print', $reportCardId))->assertOk()->assertSee('Mathematics A');

        $this->actingAs($admin)->post(route('report-cards.generate', $graph['exam']))->assertRedirect();
        $this->assertDatabaseCount('report_cards', 2);
    }

    public function test_teacher_can_view_assigned_student_report_cards_only(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graphA = $this->reportGraph($school, 'A', withResults: true, withPeerSubjectResults: true);
        $graphB = $this->reportGraph($school, 'B', withResults: true);

        $this->actingAs($admin)->post(route('report-cards.generate', $graphA['exam']));
        $this->actingAs($admin)->post(route('report-cards.generate', $graphB['exam']));

        $cardAId = (int) DB::table('report_cards')->where('exam_id', $graphA['exam']->id)->value('id');
        $cardBId = (int) DB::table('report_cards')->where('exam_id', $graphB['exam']->id)->value('id');

        $this->actingAs($graphA['teacherUser'])
            ->get(route('report-cards.index'))
            ->assertOk()
            ->assertSee('ADM-A-1')
            ->assertSee('Assigned subjects')
            ->assertDontSee('92.50%')
            ->assertDontSee('ADM-B-1');

        $this->actingAs($graphA['teacherUser'])
            ->get(route('report-cards.show', $cardAId))
            ->assertOk()
            ->assertSee('Assigned Subject View')
            ->assertSee('Mathematics A')
            ->assertDontSee('Science A')
            ->assertDontSee('92.50%')
            ->assertDontSee('185.00 / 200.00');

        $this->actingAs($graphA['teacherUser'])
            ->get(route('report-cards.print', $cardAId))
            ->assertOk()
            ->assertSee('Mathematics A')
            ->assertDontSee('Science A');

        $this->actingAs($admin)
            ->get(route('report-cards.show', $cardAId))
            ->assertOk()
            ->assertSee('Science A')
            ->assertSee('92.50%')
            ->assertSee('185.00 / 200.00');

        $this->actingAs($graphA['teacherUser'])->get(route('report-cards.show', $cardBId))->assertForbidden();
        $this->actingAs($graphA['teacherUser'])->post(route('report-cards.generate', $graphA['exam']))->assertForbidden();
    }

    public function test_generation_skips_students_without_complete_results(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->reportGraph($school, 'A', studentCount: 2, withResults: false);

        $this->actingAs($admin)->post(route('report-cards.generate', $graph['exam']))->assertRedirect();
        $this->assertDatabaseCount('report_cards', 0);
    }

    public function test_cross_tenant_report_cards_are_not_exposed_or_mutable(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $graphA = $this->reportGraph($schoolA, 'A', withResults: true);

        $this->actingAs($adminA)->post(route('report-cards.generate', $graphA['exam']));
        $reportCard = ReportCard::query()->withoutGlobalScopes()->firstOrFail();

        $this->actingAs($adminB)->get(route('report-cards.show', $reportCard))->assertNotFound();
        $this->actingAs($adminB)->post(route('report-cards.generate', $graphA['exam']))->assertNotFound();
    }

    public function test_direct_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->reportGraph($school, 'A', withResults: true);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(ReportCardService::class)->generateForExam($graph['exam'], $admin));
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    private function generateReportCard(array $graph): ReportCard
    {
        return ReportCard::withoutGlobalScopes()->create([
            'school_id' => $graph['exam']->school_id,
            'exam_id' => $graph['exam']->id,
            'student_id' => $graph['students'][0]->id,
            'academic_year_id' => $graph['year']->id,
            'class_id' => $graph['class']->id,
            'section_id' => $graph['section']->id,
            'total_marks' => '100.00',
            'marks_obtained' => '85.00',
            'percentage' => '85.00',
            'result_status' => ReportCard::STATUS_PASS,
            'generated_at' => now(),
            'generated_by' => $graph['admin']->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportGraph(
        School $school,
        string $suffix,
        int $studentCount = 1,
        bool $withResults = false,
        bool $withPeerSubjectResults = false,
    ): array {
        return $this->tenant($school, function () use ($suffix, $studentCount, $withResults, $withPeerSubjectResults, $school): array {
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
            $teacherUser = User::factory()->create([
                'school_id' => app(TenantContext::class)->tenantId(),
                'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                'email' => strtolower('report-teacher-'.$suffix).'@example.com',
            ]);
            $teacher = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'TCH-'.$suffix,
                'joining_date' => now()->subMonth()->toDateString(),
                'status' => Teacher::STATUS_ACTIVE,
            ]);
            $subject = Subject::create([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'name' => 'Mathematics '.$suffix,
                'code' => 'MATH-'.$suffix,
                'status' => Subject::STATUS_ACTIVE,
            ]);
            $exam = Exam::create([
                'academic_year_id' => $year->id,
                'name' => 'Mid Term '.$suffix,
                'exam_type' => Exam::TYPE_TERM,
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeeks(2)->toDateString(),
                'status' => Exam::STATUS_ONGOING,
            ]);
            $examSubject = ExamSubject::create([
                'exam_id' => $exam->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
                'max_marks' => '100.00',
                'passing_marks' => '33.00',
            ]);
            $peerSubject = null;
            $peerExamSubject = null;

            if ($withPeerSubjectResults) {
                $peerTeacherUser = User::factory()->create([
                    'school_id' => app(TenantContext::class)->tenantId(),
                    'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                    'email' => strtolower('report-peer-teacher-'.$suffix).'@example.com',
                ]);
                $peerTeacher = Teacher::create([
                    'user_id' => $peerTeacherUser->id,
                    'employee_code' => 'TCH-PEER-'.$suffix,
                    'joining_date' => now()->subMonth()->toDateString(),
                    'status' => Teacher::STATUS_ACTIVE,
                ]);
                $peerSubject = Subject::create([
                    'class_id' => $class->id,
                    'teacher_id' => $peerTeacher->id,
                    'name' => 'Science '.$suffix,
                    'code' => 'SCI-'.$suffix,
                    'status' => Subject::STATUS_ACTIVE,
                ]);
                $peerExamSubject = ExamSubject::create([
                    'exam_id' => $exam->id,
                    'subject_id' => $peerSubject->id,
                    'class_id' => $class->id,
                    'max_marks' => '100.00',
                    'passing_marks' => '33.00',
                ]);
            }

            app(GradeScaleService::class)->ensureDefaultScalesExist();
            $admin = User::factory()->create([
                'school_id' => $school->id,
                'role_id' => Role::query()->where('code', Role::SCHOOL_ADMIN)->value('id'),
                'email' => strtolower('report-admin-'.$suffix).'@example.com',
            ]);

            $students = [];
            for ($index = 1; $index <= $studentCount; $index++) {
                $student = Student::create([
                    'admission_no' => 'ADM-'.$suffix.'-'.$index,
                    'first_name' => 'Student',
                    'last_name' => $suffix.'-'.$index,
                    'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
                    'date_of_birth' => '2015-05-10',
                    'guardian_name' => 'Guardian',
                    'guardian_phone' => '9876500000',
                    'admission_date' => now()->subMonth()->toDateString(),
                    'status' => Student::STATUS_ACTIVE,
                ]);
                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'class_id' => $class->id,
                    'section_id' => $section->id,
                    'roll_no' => 'ROLL-'.$suffix.'-'.$index,
                    'enrollment_date' => now()->subMonth()->toDateString(),
                    'status' => StudentEnrollment::STATUS_ACTIVE,
                ]);
                $students[] = $student;

                if ($withResults) {
                    $marks = $index === 1 ? '85.00' : '20.00';
                    $status = $index === 1 ? ExamResult::STATUS_PASS : ExamResult::STATUS_FAIL;
                    ExamResult::create([
                        'exam_id' => $exam->id,
                        'exam_subject_id' => $examSubject->id,
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'marks_obtained' => $marks,
                        'grade_scale_id' => null,
                        'result_status' => $status,
                        'entered_by' => $admin->id,
                    ]);

                    if ($peerExamSubject instanceof ExamSubject && $peerSubject instanceof Subject) {
                        ExamResult::create([
                            'exam_id' => $exam->id,
                            'exam_subject_id' => $peerExamSubject->id,
                            'student_id' => $student->id,
                            'subject_id' => $peerSubject->id,
                            'marks_obtained' => $index === 1 ? '100.00' : '70.00',
                            'grade_scale_id' => null,
                            'result_status' => ExamResult::STATUS_PASS,
                            'entered_by' => $admin->id,
                        ]);
                    }
                }
            }

            return compact('year', 'class', 'section', 'subject', 'peerSubject', 'teacher', 'teacherUser', 'exam', 'examSubject', 'peerExamSubject', 'students', 'admin');
        });
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'EXM-RC-'.$suffix,
            'email' => strtolower('exam-rc-'.$suffix).'@school.example.com',
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

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
