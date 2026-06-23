<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ExamResultService;
use App\Services\GradeScaleService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamMarksEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_marks_entry_and_result_routes(): void
    {
        $school = $this->school('One');
        $graph = $this->marksGraph($school, 'A');
        $exam = $graph['exam'];
        $examSubject = $graph['examSubject'];

        foreach ([
            fn () => $this->get(route('exam-marks-entry.index')),
            fn () => $this->post(route('exam-marks-entry.store'), []),
            fn () => $this->get(route('exam-results.index')),
            fn () => $this->get(route('exam-results.show', $exam)),
            fn () => $this->patch(route('exam-results.process', $exam)),
            fn () => $this->get(route('exam-marks-entry.index', ['exam_subject_id' => $examSubject->id])),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_and_accountant_are_denied_marks_entry_and_results(): void
    {
        $school = $this->school('One');
        $graph = $this->marksGraph($school, 'A');
        $superAdmin = $this->superAdmin();
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $this->actingAs($superAdmin)->get(route('exam-marks-entry.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('exam-marks-entry.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('exam-results.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('exam-marks-entry.store'), [
            'exam_subject_id' => $graph['examSubject']->id,
            'entries' => [],
        ])->assertForbidden();
    }

    public function test_school_admin_saves_complete_roster_with_grade_calculation_and_processes_results(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->marksGraph($school, 'A', studentCount: 2);

        $this->actingAs($admin)
            ->get(route('exam-marks-entry.index', ['exam_subject_id' => $graph['examSubject']->id]))
            ->assertOk()
            ->assertSee('Save Complete Roster')
            ->assertSee('ADM-A-1');

        $payload = $this->marksPayload($graph, ['85.00', '20.00']);

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('exam_results', 2);
        $this->assertDatabaseHas('exam_results', [
            'student_id' => $graph['students'][0]->id,
            'marks_obtained' => '85.00',
            'result_status' => ExamResult::STATUS_PASS,
            'entered_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('exam_results', [
            'student_id' => $graph['students'][1]->id,
            'marks_obtained' => '20.00',
            'result_status' => ExamResult::STATUS_FAIL,
        ]);

        $gradeAId = (int) DB::table('grade_scales')
            ->where('school_id', $school->id)
            ->where('grade', 'A')
            ->value('id');
        $this->assertSame(
            $gradeAId,
            (int) DB::table('exam_results')->where('student_id', $graph['students'][0]->id)->value('grade_scale_id'),
        );

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('exam_results', 2);
        $this->assertDatabaseCount('activity_logs', 2);

        $this->actingAs($admin)->get(route('exam-results.index'))->assertOk()->assertSee('Mid Term A');
        $this->actingAs($admin)->get(route('exam-results.show', $graph['exam']))->assertOk()->assertSee('Process Results');
        $this->actingAs($admin)->patch(route('exam-results.process', $graph['exam']))->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'examination_management',
            'action' => 'processed',
            'subject_type' => Exam::class,
            'subject_id' => $graph['exam']->id,
        ]);
    }

    public function test_teacher_can_enter_marks_only_for_assigned_exam_subjects(): void
    {
        $school = $this->school('One');
        $graph = $this->marksGraph($school, 'A');
        $otherGraph = $this->marksGraph($school, 'B');
        $teacherUser = $graph['teacherUser'];

        $this->actingAs($teacherUser)
            ->get(route('exam-marks-entry.index', ['exam_subject_id' => $graph['examSubject']->id]))
            ->assertOk()
            ->assertSee('Save Complete Roster');

        $this->actingAs($teacherUser)
            ->post(route('exam-marks-entry.store'), $this->marksPayload($graph, ['72.00']))
            ->assertRedirect();

        $this->actingAs($teacherUser)
            ->get(route('exam-marks-entry.index', ['exam_subject_id' => $otherGraph['examSubject']->id]))
            ->assertNotFound();

        $this->actingAs($teacherUser)
            ->post(route('exam-marks-entry.store'), $this->marksPayload($otherGraph, ['72.00']))
            ->assertNotFound();

        $this->actingAs($teacherUser)->patch(route('exam-results.process', $graph['exam']))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('exam-results.show', $graph['exam']))->assertOk();
    }

    public function test_complete_roster_forged_fields_and_invalid_marks_are_rejected(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->marksGraph($school, 'A', studentCount: 2);

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), [
            'exam_subject_id' => $graph['examSubject']->id,
            'entries' => [[
                'student_id' => $graph['students'][0]->id,
                'marks_obtained' => '50.00',
            ]],
        ])->assertSessionHasErrors('entries');

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), [
            'exam_subject_id' => $graph['examSubject']->id,
            'entries' => collect($graph['students'])->map(fn (Student $student): array => [
                'student_id' => $student->id,
                'marks_obtained' => '150.00',
            ])->all(),
        ])->assertSessionHasErrors('entries');

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), [
            'exam_subject_id' => $graph['examSubject']->id,
            'school_id' => 999,
            'entries' => collect($graph['students'])->map(fn (Student $student): array => [
                'student_id' => $student->id,
                'marks_obtained' => '50.00',
            ])->all(),
        ])->assertSessionHasErrors('school_id');
    }

    public function test_cross_tenant_exam_results_are_not_exposed_or_mutable(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $graphA = $this->marksGraph($schoolA, 'A');

        $this->actingAs($adminB)->get(route('exam-marks-entry.index', [
            'exam_subject_id' => $graphA['examSubject']->id,
        ]))->assertNotFound();
        $this->actingAs($adminB)->get(route('exam-results.show', $graphA['exam']))->assertNotFound();
        $this->actingAs($adminB)->patch(route('exam-results.process', $graphA['exam']))->assertNotFound();
    }

    public function test_direct_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->marksGraph($school, 'A');

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(ExamResultService::class)->saveMarksRoster([
            'exam_subject_id' => $graph['examSubject']->id,
            'entries' => [[
                'student_id' => $graph['students'][0]->id,
                'marks_obtained' => '55.00',
            ]],
        ], $admin));
    }

    public function test_entered_by_remains_immutable_when_marks_are_corrected(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $otherAdmin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin-two@example.com');
        $graph = $this->marksGraph($school, 'A', studentCount: 1);

        $this->actingAs($admin)->post(route('exam-marks-entry.store'), $this->marksPayload($graph, ['55.00']));
        $resultId = (int) DB::table('exam_results')->value('id');

        $this->actingAs($otherAdmin)->post(route('exam-marks-entry.store'), $this->marksPayload($graph, ['78.00']));
        $this->assertDatabaseHas('exam_results', [
            'id' => $resultId,
            'marks_obtained' => '78.00',
            'entered_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'corrected',
            'subject_type' => ExamResult::class,
            'subject_id' => $resultId,
        ]);

        $logPayload = AuditLog::withoutGlobalScopes()->latest('id')->firstOrFail()->toJson();
        $this->assertStringNotContainsString('Private remark', $logPayload);
    }

    /**
     * @param  list<string>  $marks
     * @return array<string, mixed>
     */
    private function marksPayload(array $graph, array $marks): array
    {
        return [
            'exam_subject_id' => $graph['examSubject']->id,
            'entries' => collect($graph['students'])->values()->map(function (Student $student, int $index) use ($marks): array {
                return [
                    'student_id' => $student->id,
                    'marks_obtained' => $marks[$index] ?? '33.00',
                    'remarks' => $index === 0 ? 'Private remark' : null,
                ];
            })->all(),
        ];
    }

    /**
     * @return array{
     *     year: AcademicYear,
     *     class: SchoolClass,
     *     section: Section,
     *     subject: Subject,
     *     teacher: Teacher,
     *     teacherUser: User,
     *     exam: Exam,
     *     examSubject: ExamSubject,
     *     students: list<Student>
     * }
     */
    private function marksGraph(School $school, string $suffix, int $studentCount = 1): array
    {
        return $this->tenant($school, function () use ($suffix, $studentCount): array {
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
                'email' => strtolower('exam-teacher-'.$suffix).'@example.com',
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
            app(GradeScaleService::class)->ensureDefaultScalesExist();

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
            }

            return compact('year', 'class', 'section', 'subject', 'teacher', 'teacherUser', 'exam', 'examSubject', 'students');
        });
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'EXM-MARKS-'.$suffix,
            'email' => strtolower('exam-marks-'.$suffix).'@school.example.com',
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
