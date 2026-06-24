<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ExamService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamSetupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_exam_setup_and_assignment_routes(): void
    {
        $school = $this->school('One');
        $exam = $this->exam($school, 'Mid Term');
        $examSubject = $this->examSubject($school, $exam);

        foreach ([
            fn () => $this->get(route('exams.index')),
            fn () => $this->post(route('exams.store'), ['name' => 'Final Exam']),
            fn () => $this->get(route('exams.show', $exam)),
            fn () => $this->patch(route('exams.publish', $exam)),
            fn () => $this->get(route('exam-subjects.index')),
            fn () => $this->post(route('exam-subjects.store'), []),
            fn () => $this->get(route('exam-subjects.show', $examSubject)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_super_admin_accountant_and_teacher_are_denied_exam_setup_and_assignment(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $superAdmin = $this->superAdmin();
        $graph = $this->academicGraph($school, 'A');

        $this->actingAs($admin)->post(route('exams.store'), [
            'academic_year_id' => $graph['year']->id,
            'name' => 'Mid Term',
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ])->assertRedirect();

        $exam = Exam::query()->withoutGlobalScopes()->where('name', 'Mid Term')->firstOrFail();

        $this->actingAs($superAdmin)->get(route('exams.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('exams.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('exams.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('exams.store'), ['name' => 'Other'])->assertForbidden();
        $this->actingAs($teacher)->get(route('exam-subjects.create'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('exam-subjects.index'))->assertForbidden();
        $this->actingAs($accountant)->post(route('exam-subjects.store'), [
            'exam_id' => $exam->id,
            'class_id' => $graph['class']->id,
            'subject_id' => $graph['subject']->id,
        ])->assertForbidden();
    }

    public function test_school_admin_manages_exams_and_assignments_with_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->academicGraph($school, 'A');

        $this->actingAs($admin)->post(route('exams.store'), [
            'academic_year_id' => $graph['year']->id,
            'name' => '  Mid Term  ',
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'school_id' => 999,
            'status' => Exam::STATUS_ONGOING,
        ])->assertSessionHasErrors(['school_id', 'status']);

        $response = $this->actingAs($admin)->post(route('exams.store'), [
            'academic_year_id' => $graph['year']->id,
            'name' => '  Mid Term  ',
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ]);
        $examId = (int) DB::table('exams')->where('name', 'Mid Term')->value('id');
        $response->assertRedirect(route('exams.show', $examId));

        $this->assertSame(7, (int) DB::table('grade_scales')->where('school_id', $school->id)->count());

        $assign = $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $examId,
            'class_id' => $graph['class']->id,
            'subject_id' => $graph['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
            'school_id' => 999,
        ]);
        $assign->assertSessionHasErrors(['school_id']);
        $assign = $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $examId,
            'class_id' => $graph['class']->id,
            'subject_id' => $graph['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ]);
        $examSubjectId = (int) DB::table('exam_subjects')->value('id');
        $assign->assertRedirect(route('exam-subjects.show', $examSubjectId));

        $this->assertDatabaseHas('exam_subjects', [
            'id' => $examSubjectId,
            'school_id' => $school->id,
            'exam_id' => $examId,
            'subject_id' => $graph['subject']->id,
            'class_id' => $graph['class']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'module' => 'examination_management',
            'action' => 'assigned',
            'subject_type' => ExamSubject::class,
            'subject_id' => $examSubjectId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'auditable_type' => ExamSubject::class,
            'auditable_id' => $examSubjectId,
            'event' => 'assigned',
        ]);
    }

    public function test_assignment_rejects_duplicate_rows_invalid_marks_and_mismatched_class(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graphA = $this->academicGraph($school, 'A');
        $graphB = $this->academicGraph($school, 'B');
        $exam = $this->exam($school, 'Unit Test');

        $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $exam->id,
            'class_id' => $graphB['class']->id,
            'subject_id' => $graphA['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ])->assertSessionHasErrors(['subject_id']);

        $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $exam->id,
            'class_id' => $graphA['class']->id,
            'subject_id' => $graphA['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '150.00',
        ])->assertSessionHasErrors(['passing_marks']);

        $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $exam->id,
            'class_id' => $graphA['class']->id,
            'subject_id' => $graphA['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('exam-subjects.store'), [
            'exam_id' => $exam->id,
            'class_id' => $graphA['class']->id,
            'subject_id' => $graphA['subject']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ])->assertSessionHasErrors(['subject_id']);
    }

    public function test_cross_tenant_exam_records_are_not_exposed_or_mutable(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $examA = $this->exam($schoolA, 'Mid Term A');
        $examSubjectA = $this->examSubject($schoolA, $examA);

        $this->actingAs($adminB)->get(route('exams.show', $examA))->assertNotFound();
        $this->actingAs($adminB)->put(route('exams.update', $examA), [
            'exam_type' => Exam::TYPE_FINAL,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ])->assertNotFound();
        $this->actingAs($adminB)->get(route('exam-subjects.show', $examSubjectA))->assertNotFound();
    }

    public function test_direct_services_reject_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $graph = $this->academicGraph($school, 'A');

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(ExamService::class)->create([
            'academic_year_id' => $graph['year']->id,
            'name' => 'Invalid',
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ], $admin));
    }

    public function test_exam_setup_routes_match_contract_without_destroy(): void
    {
        $this->assertNull(collect(app('router')->getRoutes())->first(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'exams')));
        $this->assertNull(collect(app('router')->getRoutes())->first(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'exam-subjects')));
    }

    /**
     * @return array{year: AcademicYear, class: SchoolClass, section: Section, subject: Subject}
     */
    private function academicGraph(School $school, string $suffix): array
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
            $teacherUser = User::factory()->create([
                'school_id' => app(TenantContext::class)->tenantId(),
                'role_id' => Role::query()->where('code', Role::TEACHER)->value('id'),
                'email' => strtolower('exam-teacher-'.$suffix).'@example.com',
            ]);
            $teacher = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'TCH-'.$suffix,
                'joining_date' => now()->subMonth()->toDateString(),
            ]);
            $subject = Subject::create([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'name' => 'Mathematics '.$suffix,
                'code' => 'MATH-'.$suffix,
                'status' => Subject::STATUS_ACTIVE,
            ]);

            return compact('year', 'class', 'section', 'subject');
        });
    }

    private function exam(School $school, string $name): Exam
    {
        $graph = $this->academicGraph($school, md5($name));

        return $this->tenant($school, fn () => Exam::create([
            'academic_year_id' => $graph['year']->id,
            'name' => $name,
            'exam_type' => Exam::TYPE_TERM,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'status' => Exam::STATUS_SCHEDULED,
        ]));
    }

    private function examSubject(School $school, Exam $exam): ExamSubject
    {
        $graph = $this->academicGraph($school, 'SUB-'.$exam->id);

        return $this->tenant($school, fn () => ExamSubject::create([
            'exam_id' => $exam->id,
            'subject_id' => $graph['subject']->id,
            'class_id' => $graph['class']->id,
            'max_marks' => '100.00',
            'passing_marks' => '33.00',
        ]));
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'EXM-SETUP-'.$suffix,
            'email' => strtolower('exam-setup-'.$suffix).'@school.example.com',
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
