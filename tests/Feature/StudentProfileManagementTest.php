<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SecurityLogService;
use App\Services\StudentService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class StudentProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_and_accountant_cannot_access_student_management(): void
    {
        $school = $this->school('One');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');

        $this->get(route('students.index'))->assertRedirect(route('login'));
        $this->actingAs($accountant)->get(route('students.index'))->assertForbidden();
        $this->actingAs($accountant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('students.index'), false);
    }

    public function test_school_admin_can_register_view_and_update_a_private_student_profile(): void
    {
        Storage::fake('local');
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $payload = [
            ...$this->studentPayload('ONE'),
            'photo' => UploadedFile::fake()->image('student.png', 320, 400)->size(150),
        ];

        $response = $this->actingAs($admin)->post(route('students.store'), $payload);
        $student = $this->tenant($school, fn () => Student::query()->firstOrFail());

        $response->assertRedirect(route('students.show', $student));
        $this->assertSame($school->id, $student->school_id);
        $this->assertSame(Student::STATUS_ACTIVE, $student->status);
        $this->assertNotNull($student->photo_path);
        Storage::disk('local')->assertExists($student->photo_path);

        $this->actingAs($admin)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Guardian ONE')
            ->assertSee('9876500001')
            ->assertSee(route('students.photo.show', $student), false);

        $this->actingAs($admin)
            ->put(route('students.update', $student), [
                ...$this->studentUpdatePayload('UPDATED'),
                'first_name' => 'Updated',
            ])
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_id' => $school->id,
            'admission_no' => 'ADM-ONE',
            'first_name' => 'Updated',
        ]);

        $logs = $this->tenant($school, fn () => [
            ActivityLog::query()->where('subject_id', $student->id)->get(),
            AuditLog::query()->where('auditable_id', $student->id)->get(),
        ]);
        $this->assertCount(2, $logs[0]);
        $this->assertCount(2, $logs[1]);
        $serializedAudit = $logs[1]->toJson();
        $this->assertStringNotContainsString('date_of_birth', $serializedAudit);
        $this->assertStringNotContainsString('guardian', $serializedAudit);
        $this->assertStringNotContainsString('photo_path', $serializedAudit);
        $this->assertStringNotContainsString('9876500001', $serializedAudit);
    }

    public function test_validation_rejects_forged_ownership_invalid_private_data_and_reserved_admission_numbers(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $this->tenant($school, fn () => $student->delete());

        $this->actingAs($admin)
            ->post(route('students.store'), [
                ...$this->studentPayload('INVALID'),
                'admission_no' => $student->admission_no,
                'date_of_birth' => now()->addDay()->toDateString(),
                'admission_date' => now()->addDay()->toDateString(),
                'guardian_phone' => 'not-a-phone',
                'school_id' => $school->id + 999,
                'status' => Student::STATUS_GRADUATED,
                'photo_path' => 'public/leak.png',
            ])
            ->assertSessionHasErrors([
                'admission_no',
                'date_of_birth',
                'admission_date',
                'guardian_phone',
                'school_id',
                'status',
                'photo_path',
            ]);

        $active = $this->student($school, 'ACTIVE');
        $this->actingAs($admin)
            ->put(route('students.update', $active), [
                ...$this->studentPayload('UPDATE'),
                'admission_no' => 'FORGED',
                'photo_path' => 'public/leak.png',
            ])
            ->assertSessionHasErrors(['admission_no', 'photo_path']);

        $this->assertDatabaseHas('students', [
            'id' => $active->id,
            'admission_no' => 'ADM-ACTIVE',
            'photo_path' => null,
        ]);
    }

    public function test_cross_tenant_student_and_photo_routes_return_not_found(): void
    {
        Storage::fake('local');
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminA = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin-a@example.com');
        $studentB = $this->student($schoolB, 'B', ['photo_path' => 'student-photos/'.$schoolB->id.'/b.png']);
        Storage::disk('local')->put($studentB->photo_path, 'private-photo');

        $this->actingAs($adminA)->get(route('students.show', $studentB))->assertNotFound();
        $this->actingAs($adminA)->get(route('students.photo.show', $studentB))->assertNotFound();
        $this->actingAs($adminA)->put(route('students.update', $studentB), $this->studentPayload('FORGED'))->assertNotFound();
        $this->actingAs($adminA)->patch(route('students.deactivate', $studentB))->assertNotFound();
        $this->actingAs($adminA)->delete(route('students.photo.destroy', $studentB))->assertNotFound();

        $this->actingAs($adminA)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('ADM-B');
    }

    public function test_teacher_sees_only_active_students_reached_by_section_or_subject_assignment(): void
    {
        $school = $this->school('One');
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $teacher = $this->teacher($school, $teacherUser);
        $sectionAssigned = $this->enrolledStudent($school, 'SECTION', $teacher, false);
        $subjectAssigned = $this->enrolledStudent($school, 'SUBJECT', $teacher, true);
        $unassigned = $this->enrolledStudent($school, 'OTHER');
        $this->additionalEnrollment($school, $sectionAssigned['student'], 'HIDDEN');

        $index = $this->actingAs($teacherUser)->get(route('students.index'));
        $index->assertOk()
            ->assertSee('ADM-SECTION')
            ->assertSee('ADM-SUBJECT')
            ->assertDontSee('ADM-OTHER')
            ->assertDontSee('Register Student');

        foreach ([$sectionAssigned['student'], $subjectAssigned['student']] as $student) {
            $this->actingAs($teacherUser)
                ->get(route('students.show', $student))
                ->assertOk()
                ->assertSee('privacy-minimized')
                ->assertDontSee($student->guardian_name)
                ->assertDontSee($student->guardian_phone)
                ->assertDontSee($student->guardian_email)
                ->assertDontSee($student->address)
                ->assertDontSee($student->date_of_birth->format('d M Y'))
                ->assertDontSee(route('students.photo.show', $student), false);
        }
        $this->actingAs($teacherUser)
            ->get(route('students.show', $sectionAssigned['student']))
            ->assertOk()
            ->assertDontSee('ROLL-HIDDEN');

        $this->actingAs($teacherUser)->get(route('students.show', $unassigned['student']))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('students.photo.show', $sectionAssigned['student']))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('students.create'))->assertForbidden();

        $this->tenant($school, function () use ($sectionAssigned): void {
            $sectionAssigned['section']->forceFill(['status' => Section::STATUS_INACTIVE])->save();
        });
        $this->actingAs($teacherUser)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('ADM-SECTION')
            ->assertSee('ADM-SUBJECT');
    }

    public function test_super_admin_requires_school_selection_and_receives_privacy_minimized_read_only_access(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $studentA = $this->student($schoolA, 'A');
        $studentB = $this->student($schoolB, 'B');
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Select one school')
            ->assertDontSee('ADM-A')
            ->assertDontSee('ADM-B');

        $this->actingAs($superAdmin)
            ->get(route('students.index', ['school_id' => $schoolA->id]))
            ->assertOk()
            ->assertSee('ADM-A')
            ->assertDontSee('ADM-B')
            ->assertDontSee('Register Student');

        $this->actingAs($superAdmin)
            ->get(route('students.show', ['student' => $studentA, 'school_id' => $schoolA->id]))
            ->assertOk()
            ->assertSee('privacy-minimized')
            ->assertDontSee($studentA->guardian_name)
            ->assertDontSee($studentA->guardian_phone)
            ->assertDontSee($studentA->date_of_birth->format('d M Y'));

        $this->actingAs($superAdmin)
            ->get(route('students.show', ['student' => $studentA, 'school_id' => $schoolB->id]))
            ->assertNotFound();
        $this->actingAs($superAdmin)->get(route('students.show', $studentA))->assertSessionHasErrors('school_id');
        $this->actingAs($superAdmin)->post(route('students.store'), $this->studentPayload('FORGED'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('students.photo.show', $studentA))->assertForbidden();
        $this->assertDatabaseHas('students', ['id' => $studentB->id, 'school_id' => $schoolB->id]);
    }

    public function test_lifecycle_preserves_history_and_enforces_archive_dependencies(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');

        $this->actingAs($admin)->patch(route('students.deactivate', $student))->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_INACTIVE]);
        $this->actingAs($admin)->patch(route('students.archive', $student))->assertRedirect();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->actingAs($admin)->patch(route('students.restore', $student))->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_INACTIVE, 'deleted_at' => null]);
        $this->actingAs($admin)->patch(route('students.activate', $student))->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => Student::STATUS_ACTIVE]);

        $structure = $this->enrolledStudent($school, 'ENROLLED');
        $this->actingAs($admin)->patch(route('students.deactivate', $structure['student']))->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('students.archive', $structure['student']))
            ->assertSessionHasErrors('status');
        $this->assertNotSoftDeleted('students', ['id' => $structure['student']->id]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $structure['enrollment']->id,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

        $terminal = $this->student($school, 'TERMINAL', ['status' => Student::STATUS_GRADUATED]);
        $this->actingAs($admin)->get(route('students.edit', $terminal))->assertForbidden();
        $this->actingAs($admin)->delete('/students/'.$terminal->id)->assertMethodNotAllowed();
    }

    public function test_private_photo_validation_delivery_replacement_removal_and_retention_follow_policy(): void
    {
        Storage::fake('local');
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');

        $this->actingAs($admin)
            ->put(route('students.photo.update', $student), [
                'photo' => UploadedFile::fake()->create('forged.jpg', 20, 'text/plain'),
            ])
            ->assertSessionHasErrors('photo');
        $this->actingAs($admin)
            ->put(route('students.photo.update', $student), [
                'photo' => UploadedFile::fake()->image('large.png')->size(2049),
            ])
            ->assertSessionHasErrors('photo');

        $this->actingAs($admin)
            ->put(route('students.photo.update', $student), [
                'photo' => UploadedFile::fake()->image('first.png', 240, 300)->size(100),
            ])
            ->assertRedirect(route('students.show', $student));
        $firstPath = $this->tenant($school, fn () => $student->fresh()->photo_path);
        $this->assertStringStartsWith('student-photos/'.$school->id.'/', $firstPath);
        $this->assertStringNotContainsString('first.png', $firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $photoResponse = $this->actingAs($admin)
            ->get(route('students.photo.show', $student))
            ->assertOk();
        $this->assertStringContainsString('private', (string) $photoResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $photoResponse->headers->get('Cache-Control'));

        $this->actingAs($admin)
            ->put(route('students.photo.update', $student), [
                'photo' => UploadedFile::fake()->image('replacement.webp', 240, 300)->size(100),
            ])
            ->assertRedirect();
        $secondPath = $this->tenant($school, fn () => $student->fresh()->photo_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);

        $this->actingAs($admin)->patch(route('students.deactivate', $student))->assertRedirect();
        $this->actingAs($admin)->get(route('students.photo.show', $student))->assertForbidden();
        Storage::disk('local')->assertExists($secondPath);
        $this->actingAs($admin)->patch(route('students.activate', $student))->assertRedirect();
        $this->actingAs($admin)->delete(route('students.photo.destroy', $student))->assertRedirect();
        Storage::disk('local')->assertMissing($secondPath);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'photo_path' => null]);

        Storage::disk('local')->put('student-photos/'.$school->id.'/retained.png', 'retained');
        $this->tenant($school, fn () => $student->forceFill(['photo_path' => 'student-photos/'.$school->id.'/retained.png'])->save());
        $this->actingAs($admin)->patch(route('students.deactivate', $student))->assertRedirect();
        $this->actingAs($admin)->patch(route('students.archive', $student))->assertRedirect();
        Storage::disk('local')->assertExists('student-photos/'.$school->id.'/retained.png');
        $this->actingAs($admin)->get(route('students.photo.show', $student))->assertNotFound();
    }

    public function test_permission_revocation_updates_student_routes_actions_and_navigation(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $student = $this->student($school, 'ONE');
        $role = Role::query()->where('code', Role::SCHOOL_ADMIN)->firstOrFail();

        $this->actingAs($admin)->get(route('students.index'))->assertOk()->assertSee('Register Student');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee(route('students.index'), false);

        $view = Permission::query()->where('code', 'students.view')->firstOrFail();
        $role->permissions()->detach($view);
        $this->actingAs($admin)->get(route('students.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('students.index'), false);

        $role->permissions()->attach($view);
        $create = Permission::query()->where('code', 'students.create')->firstOrFail();
        $role->permissions()->detach($create);
        $this->actingAs($admin)->get(route('students.create'))->assertForbidden();

        $update = Permission::query()->where('code', 'students.update')->firstOrFail();
        $role->permissions()->detach($update);
        $this->actingAs($admin)->get(route('students.edit', $student))->assertForbidden();

        $delete = Permission::query()->where('code', 'students.delete')->firstOrFail();
        $role->permissions()->detach($delete);
        $this->actingAs($admin)->patch(route('students.deactivate', $student))->assertForbidden();
    }

    public function test_direct_services_reject_unresolved_wrong_context_and_platform_mutation(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolA, 'admin@example.com');
        $superAdmin = $this->superAdmin();
        $service = app(StudentService::class);
        $context = app(TenantContext::class);

        foreach ([
            fn () => $service->listFor($admin, []),
            fn () => $context->runAsPlatform(fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolB->id, fn () => $service->listFor($admin, [])),
            fn () => $context->runAsTenant($schoolA->id, fn () => $service->listFor($superAdmin, ['school_id' => $schoolA->id])),
            fn () => $context->runAsPlatform(fn () => $service->create($this->studentPayload('PLATFORM'), $superAdmin)),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An authorization exception was expected.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_logging_failure_rolls_back_student_and_photo_mutations_without_losing_prior_file(): void
    {
        Storage::fake('local');
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->assertTrue($admin->canEstablishTenantContext());
        $this->assertTrue($admin->hasPermission('students.create'));
        $this->assertTrue($admin->can('create', Student::class));
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $logger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced audit failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(StudentService::class);

        try {
            $this->tenant($school, fn () => $service->create([
                ...$this->studentPayload('ROLLBACK'),
                'photo' => UploadedFile::fake()->image('rollback.png'),
            ], $admin));
            $this->fail('The forced audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        }
        $this->assertDatabaseMissing('students', ['admission_no' => 'ADM-ROLLBACK']);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $student = $this->student($school, 'EXISTING', ['photo_path' => 'student-photos/'.$school->id.'/existing.png']);
        Storage::disk('local')->put($student->photo_path, 'existing');
        $this->assertTrue($admin->hasPermission('students.update'), 'School Admin lost students.update permission.');
        $this->assertTrue($admin->can('updatePhoto', $student), 'Student photo policy denied the School Admin.');
        $photoLogger = Mockery::mock(SecurityLogService::class);
        $photoLogger->shouldReceive('activity')->once()->andReturn(new ActivityLog);
        $photoLogger->shouldReceive('audit')->once()->andThrow(new RuntimeException('Forced photo audit failure.'));
        $this->app->instance(SecurityLogService::class, $photoLogger);
        $photoService = app(StudentService::class);

        try {
            $this->tenant($school, fn () => $photoService->replacePhoto(
                $student,
                UploadedFile::fake()->image('new.png'),
                $admin,
            ));
            $this->fail('The forced photo audit failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced photo audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('students', ['id' => $student->id, 'photo_path' => $student->photo_path]);
        Storage::disk('local')->assertExists($student->photo_path);
        $this->assertSame([$student->photo_path], Storage::disk('local')->allFiles());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function school(string $suffix, array $attributes = []): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'STUDENT-'.$suffix,
            'email' => strtolower('student-'.$suffix).'@example.com',
            'status' => School::STATUS_ACTIVE,
            ...$attributes,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'name' => str($roleCode)->replace('_', ' ')->title()->toString(),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function superAdmin(): User
    {
        return User::query()
            ->whereNull('school_id')
            ->whereHas('role', fn ($query) => $query->where('code', Role::SUPER_ADMIN))
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function student(School $school, string $suffix, array $attributes = []): Student
    {
        return $this->tenant($school, fn () => Student::create([
            ...$this->studentPayload($suffix),
            'status' => Student::STATUS_ACTIVE,
            ...$attributes,
        ]));
    }

    private function teacher(School $school, User $user): Teacher
    {
        return $this->tenant($school, fn () => Teacher::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-'.$user->id,
            'status' => Teacher::STATUS_ACTIVE,
        ]));
    }

    /**
     * @return array{student: Student, year: AcademicYear, class: SchoolClass, section: Section, enrollment: StudentEnrollment}
     */
    private function enrolledStudent(
        School $school,
        string $suffix,
        ?Teacher $teacher = null,
        bool $subjectAssignment = false,
    ): array {
        return $this->tenant($school, function () use ($school, $suffix, $teacher, $subjectAssignment): array {
            $year = AcademicYear::create([
                'name' => '2026-2027 '.$suffix,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $class->id,
                'teacher_id' => $subjectAssignment ? null : $teacher?->id,
                'name' => 'Section '.$suffix,
                'status' => Section::STATUS_ACTIVE,
            ]);

            if ($subjectAssignment && $teacher instanceof Teacher) {
                Subject::create([
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'name' => 'Subject '.$suffix,
                    'code' => 'SUB-'.$suffix,
                    'subject_type' => Subject::TYPE_THEORY,
                    'status' => Subject::STATUS_ACTIVE,
                ]);
            }

            $student = Student::create([
                ...$this->studentPayload($suffix),
                'status' => Student::STATUS_ACTIVE,
            ]);
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-'.$suffix,
                'enrollment_date' => '2026-04-02',
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);

            $this->assertSame($school->id, $student->school_id);

            return compact('student', 'year', 'class', 'section', 'enrollment');
        });
    }

    private function additionalEnrollment(School $school, Student $student, string $suffix): StudentEnrollment
    {
        return $this->tenant($school, function () use ($student, $suffix): StudentEnrollment {
            $year = AcademicYear::create([
                'name' => '2027-2028 '.$suffix,
                'start_date' => '2027-04-01',
                'end_date' => '2028-03-31',
                'is_current' => false,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $class->id,
                'name' => 'Section '.$suffix,
                'status' => Section::STATUS_ACTIVE,
            ]);

            return StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-'.$suffix,
                'enrollment_date' => '2027-04-02',
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function studentPayload(string $suffix): array
    {
        return [
            'admission_no' => 'ADM-'.$suffix,
            'first_name' => 'Student',
            'last_name' => $suffix,
            'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
            'date_of_birth' => '2015-05-10',
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '9876500001',
            'guardian_email' => strtolower($suffix).'@guardian.example.com',
            'address' => 'Private address '.$suffix,
            'admission_date' => '2026-04-02',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function studentUpdatePayload(string $suffix): array
    {
        $payload = $this->studentPayload($suffix);
        unset($payload['admission_no']);

        return $payload;
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
