<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\SecurityLogService;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
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

    public function test_school_admin_can_load_save_correct_and_repeat_a_complete_roster_idempotently(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];

        $this->actingAs($admin)
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'class_id' => $structure['class']->id,
                'section_id' => $structure['section']->id,
            ]))
            ->assertOk()
            ->assertSee('Student ONE-1')
            ->assertSee('Student ONE-2')
            ->assertSee('Mark All Present')
            ->assertSee('Save Complete Roster');

        $payload = $this->rosterPayload($structure, [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_ABSENT,
        ]);

        $this->actingAs($admin)->post(route('attendance.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $structure['students'][0]->id,
            'status' => Attendance::STATUS_PRESENT,
            'marked_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('audit_logs', 2);

        $this->actingAs($admin)->post(route('attendance.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('audit_logs', 2);

        $payload['entries'][0]['status'] = Attendance::STATUS_LATE;
        $payload['entries'][0]['remarks'] = 'Private operational note';
        $this->actingAs($admin)->post(route('attendance.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $structure['students'][0]->id,
            'status' => Attendance::STATUS_LATE,
            'remarks' => 'Private operational note',
            'marked_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseCount('audit_logs', 3);

        $logPayload = AuditLog::withoutGlobalScopes()->latest('id')->firstOrFail()->toJson();
        $this->assertStringNotContainsString('Private operational note', $logPayload);
        $this->assertStringContainsString('remarks_changed', $logPayload);
    }

    public function test_holiday_transitions_are_school_admin_only_and_require_the_complete_roster(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];
        $teacher = $structure['teacherUser'];

        $this->actingAs($admin)->post(route('attendance.store'), [
            'attendance_date' => '2026-06-20',
            'section_id' => $structure['section']->id,
            'mode' => 'holiday',
        ])->assertRedirect();

        $this->assertSame(
            [Attendance::STATUS_HOLIDAY],
            Attendance::withoutGlobalScopes()->distinct()->pluck('status')->all(),
        );
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('audit_logs', 2);
        $holidayAttendance = Attendance::withoutGlobalScopes()
            ->where('student_id', $structure['students'][0]->id)
            ->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('attendance.edit', $holidayAttendance))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->patch(route('attendance.update', $holidayAttendance), [
                'status' => Attendance::STATUS_PRESENT,
                'remarks' => null,
            ])
            ->assertForbidden();
        $this->actingAs($teacher)
            ->post(route('attendance.store'), $this->rosterPayload($structure))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('attendance.update', $holidayAttendance), [
                'status' => Attendance::STATUS_PRESENT,
                'remarks' => null,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            [Attendance::STATUS_HOLIDAY],
            Attendance::withoutGlobalScopes()->distinct()->pluck('status')->all(),
        );
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('audit_logs', 2);

        $this->actingAs($admin)
            ->post(route('attendance.store'), $this->rosterPayload($structure))
            ->assertRedirect();

        $this->assertSame(
            [Attendance::STATUS_PRESENT],
            Attendance::withoutGlobalScopes()->distinct()->pluck('status')->all(),
        );
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseCount('audit_logs', 4);

        $this->actingAs($admin)
            ->patch(route('attendance.update', $holidayAttendance), [
                'status' => Attendance::STATUS_HOLIDAY,
                'remarks' => null,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('attendances', [
            'id' => $holidayAttendance->id,
            'status' => Attendance::STATUS_PRESENT,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseCount('audit_logs', 4);

        $this->actingAs($teacher)->post(route('attendance.store'), [
            'attendance_date' => '2026-06-21',
            'section_id' => $structure['section']->id,
            'mode' => 'holiday',
        ])->assertForbidden();

        $this->assertDatabaseMissing('attendances', ['attendance_date' => '2026-06-21']);
    }

    public function test_holiday_transition_keeps_lifecycle_changed_retained_students_in_the_complete_roster(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];

        $this->actingAs($admin)->post(route('attendance.store'), [
            'attendance_date' => '2026-06-20',
            'section_id' => $structure['section']->id,
            'mode' => 'holiday',
        ])->assertRedirect();

        $structure['students'][0]->forceFill(['status' => Student::STATUS_INACTIVE])->save();
        $structure['enrollments'][0]
            ->forceFill(['status' => StudentEnrollment::STATUS_COMPLETED])
            ->save();

        $this->actingAs($admin)
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertOk()
            ->assertSee('Student ONE-1')
            ->assertSee('Student ONE-2');

        $incomplete = $this->rosterPayload($structure);
        array_shift($incomplete['entries']);
        $this->actingAs($admin)->post(route('attendance.store'), $incomplete)
            ->assertSessionHasErrors('entries');
        $this->assertSame(
            [Attendance::STATUS_HOLIDAY],
            Attendance::withoutGlobalScopes()->distinct()->pluck('status')->all(),
        );

        $this->actingAs($admin)
            ->post(route('attendance.store'), $this->rosterPayload($structure, [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_ABSENT,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $structure['students'][0]->id,
            'status' => Attendance::STATUS_PRESENT,
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $structure['students'][1]->id,
            'status' => Attendance::STATUS_ABSENT,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseCount('audit_logs', 4);
    }

    public function test_teacher_scope_requires_a_direct_active_section_assignment(): void
    {
        $structure = $this->structure('ONE');
        $teacher = $structure['teacherUser'];
        $otherSection = $structure['otherSection'];

        app(TenantContext::class)->runAsTenant($structure['school']->id, function () use ($structure, $otherSection): void {
            Subject::create([
                'class_id' => $structure['class']->id,
                'teacher_id' => $structure['teacher']->id,
                'name' => 'Subject Assignment Only',
                'code' => 'SUB-ONLY',
            ]);
            $this->addStudentEnrollment($structure, 'OTHER', $otherSection);
        });

        $this->actingAs($teacher)
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertOk()
            ->assertSee('Student ONE-1');

        $this->actingAs($teacher)
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $otherSection->id,
            ]))
            ->assertNotFound();

        $structure['section']->forceFill(['teacher_id' => null])->save();

        $this->actingAs($teacher)
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertNotFound();

        $structure['section']->forceFill([
            'teacher_id' => $structure['teacher']->id,
            'status' => Section::STATUS_INACTIVE,
        ])->save();
        $this->actingAs($structure['admin'])
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertNotFound();

        $structure['section']->forceFill(['status' => Section::STATUS_ACTIVE])->save();
        $structure['class']->forceFill(['status' => SchoolClass::STATUS_INACTIVE])->save();
        $this->actingAs($structure['admin'])
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertNotFound();

        $structure['class']->forceFill(['status' => SchoolClass::STATUS_ACTIVE])->save();
        $structure['teacher']->forceFill(['status' => Teacher::STATUS_INACTIVE])->save();
        $this->actingAs($teacher)->get(route('attendance.index'))->assertForbidden();
    }

    public function test_only_school_admin_and_teacher_receive_phase_seven_routes_and_navigation(): void
    {
        $structure = $this->structure('ONE');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $structure['school'], 'accountant@example.com');
        $superAdmin = User::query()->where('email', 'superadmin@example.com')->firstOrFail();

        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->actingAs($structure['admin'])->get(route('dashboard'))->assertOk()->assertSee('Attendance this month');
        $this->actingAs($structure['teacherUser'])->get(route('dashboard'))->assertOk()->assertSee('Attendance this month');
        $this->actingAs($accountant)->get(route('dashboard'))->assertOk()->assertDontSee('Attendance');
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertOk()->assertDontSee('Attendance');

        $this->actingAs($accountant)->get(route('attendance.index'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('attendance.index'))->assertForbidden();

        $routeNames = collect(app('router')->getRoutes()->getRoutes())->pluck('action.as')->filter();
        $this->assertFalse($routeNames->contains('attendance.destroy'));
        $this->assertFalse($routeNames->contains('attendance.report'));
        $this->assertFalse($routeNames->contains('attendance.export'));
    }

    public function test_complete_roster_validation_rejects_omitted_duplicate_extraneous_and_invalid_rows_atomically(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];
        $payload = $this->rosterPayload($structure);

        $omitted = $payload;
        array_pop($omitted['entries']);
        $this->actingAs($admin)->post(route('attendance.store'), $omitted)->assertSessionHasErrors('entries');

        $duplicate = $payload;
        $duplicate['entries'][1]['student_id'] = $duplicate['entries'][0]['student_id'];
        $this->actingAs($admin)->post(route('attendance.store'), $duplicate)
            ->assertSessionHasErrors('entries.1.student_id');

        $extraneous = $payload;
        $extraneous['entries'][] = [
            'student_id' => 999999,
            'status' => Attendance::STATUS_PRESENT,
            'remarks' => null,
        ];
        $this->actingAs($admin)->post(route('attendance.store'), $extraneous)->assertSessionHasErrors('entries');

        $invalid = $payload;
        $invalid['entries'][1]['status'] = Attendance::STATUS_HOLIDAY;
        $this->actingAs($admin)->post(route('attendance.store'), $invalid)
            ->assertSessionHasErrors('entries.1.status');

        $oversized = $payload;
        $oversized['entries'][0]['remarks'] = str_repeat('x', 501);
        $this->actingAs($admin)->post(route('attendance.store'), $oversized)
            ->assertSessionHasErrors('entries.0.remarks');

        $forged = $payload + ['school_id' => 999999, 'marked_by' => 999999, 'class_id' => 999999];
        $this->actingAs($admin)->post(route('attendance.store'), $forged)
            ->assertSessionHasErrors(['school_id', 'marked_by', 'class_id']);

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_roster_is_derived_from_active_lifecycle_and_enrollment_date(): void
    {
        $structure = $this->structure('ONE', 1);

        app(TenantContext::class)->runAsTenant($structure['school']->id, function () use (&$structure): void {
            $inactive = $this->addStudentEnrollment($structure, 'INACTIVE');
            $inactive['student']->forceFill(['status' => Student::STATUS_INACTIVE])->save();
            $archived = $this->addStudentEnrollment($structure, 'ARCHIVED');
            $archived['student']->delete();
            $completed = $this->addStudentEnrollment($structure, 'COMPLETED');
            $completed['enrollment']->forceFill(['status' => StudentEnrollment::STATUS_COMPLETED])->save();
            $transferred = $this->addStudentEnrollment($structure, 'TRANSFERRED');
            $transferred['enrollment']->forceFill(['status' => StudentEnrollment::STATUS_TRANSFERRED])->save();
            $future = $this->addStudentEnrollment($structure, 'FUTURE', null, '2026-06-21');
            $otherClass = SchoolClass::create([
                'name' => 'Mismatched Class',
                'code' => 'MISMATCHED',
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $mismatched = $this->addStudentEnrollment($structure, 'MISMATCHED');
            $mismatched['enrollment']->setRawAttributes([
                ...$mismatched['enrollment']->getAttributes(),
                'class_id' => $otherClass->id,
            ])->saveQuietly();

            $structure['excluded'] = [$inactive, $archived, $completed, $transferred, $future, $mismatched];
        });

        $this->actingAs($structure['admin'])
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $structure['section']->id,
            ]))
            ->assertOk()
            ->assertSee('Student ONE-1')
            ->assertDontSee('Student INACTIVE')
            ->assertDontSee('Student ARCHIVED')
            ->assertDontSee('Student COMPLETED')
            ->assertDontSee('Student TRANSFERRED')
            ->assertDontSee('Student MISMATCHED')
            ->assertDontSee('Student FUTURE');

        $this->actingAs($structure['admin'])
            ->post(route('attendance.store'), $this->rosterPayload($structure))
            ->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_school_local_date_and_current_year_rules_reject_invalid_writes_and_historical_corrections(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];

        $future = $this->rosterPayload($structure);
        $future['attendance_date'] = '2026-06-23';
        $this->actingAs($admin)->post(route('attendance.store'), $future)->assertSessionHasErrors('attendance_date');

        $outside = $this->rosterPayload($structure);
        $outside['attendance_date'] = '2026-03-31';
        $this->actingAs($admin)->post(route('attendance.store'), $outside)->assertSessionHasErrors('attendance_date');
        $this->assertDatabaseCount('attendances', 0);

        $historicalAttendance = app(TenantContext::class)->runAsTenant(
            $structure['school']->id,
            function () use ($structure, $admin): Attendance {
                $historicalYear = AcademicYear::create([
                    'name' => '2025-2026',
                    'start_date' => '2025-04-01',
                    'end_date' => '2026-03-31',
                    'is_current' => false,
                ]);

                return Attendance::create([
                    'student_id' => $structure['students'][0]->id,
                    'academic_year_id' => $historicalYear->id,
                    'class_id' => $structure['class']->id,
                    'section_id' => $structure['section']->id,
                    'attendance_date' => '2026-03-20',
                    'status' => Attendance::STATUS_PRESENT,
                    'marked_by' => $admin->id,
                ]);
            },
        );

        $this->actingAs($admin)
            ->patch(route('attendance.update', $historicalAttendance), [
                'status' => Attendance::STATUS_ABSENT,
                'remarks' => null,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('attendance.history', ['date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('2026-03-20')
            ->assertSee('Read only');

        $this->assertDatabaseHas('attendances', [
            'id' => $historicalAttendance->id,
            'status' => Attendance::STATUS_PRESENT,
        ]);
    }

    public function test_correction_preserves_original_marker_and_records_privacy_safe_actor_evidence(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];
        $teacher = $structure['teacherUser'];
        $this->actingAs($admin)->post(route('attendance.store'), $this->rosterPayload($structure));
        $attendance = Attendance::withoutGlobalScopes()->where('student_id', $structure['students'][0]->id)->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('attendance.edit', $attendance))
            ->assertOk()
            ->assertSee('Original Marker');

        $this->actingAs($teacher)
            ->patch(route('attendance.update', $attendance), [
                'status' => Attendance::STATUS_LEAVE,
                'remarks' => 'Confidential operational note',
            ])
            ->assertRedirect(route('attendance.history'));

        $attendance->refresh();
        $this->assertSame($admin->id, $attendance->marked_by);
        $this->assertSame(Attendance::STATUS_LEAVE, $attendance->status);
        $audit = AuditLog::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame($teacher->id, $audit->user_id);
        $this->assertStringNotContainsString('Confidential operational note', $audit->toJson());
        $this->assertTrue((bool) $audit->new_values['remarks_changed']);
    }

    public function test_direct_correction_revalidates_remark_type_and_length_without_mutation(): void
    {
        $structure = $this->structure('ONE');
        $admin = $structure['admin'];
        $this->actingAs($admin)->post(route('attendance.store'), $this->rosterPayload($structure));
        $attendance = Attendance::withoutGlobalScopes()
            ->where('student_id', $structure['students'][0]->id)
            ->firstOrFail();
        $service = app(AttendanceService::class);

        foreach ([str_repeat('x', 501), ['not', 'text']] as $invalidRemarks) {
            try {
                app(TenantContext::class)->runAsTenant(
                    $structure['school']->id,
                    fn () => $service->correct($attendance, [
                        'status' => Attendance::STATUS_ABSENT,
                        'remarks' => $invalidRemarks,
                    ], $admin),
                );
                $this->fail('Invalid direct-service Attendance remarks were accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('remarks', $exception->errors());
            }
        }

        $attendance->refresh();
        $this->assertSame(Attendance::STATUS_PRESENT, $attendance->status);
        $this->assertNull($attendance->remarks);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_history_search_and_monthly_summary_are_tenant_and_assignment_scoped(): void
    {
        $schoolA = $this->structure('A');
        $schoolB = $this->structure('B');
        $this->actingAs($schoolA['admin'])->post(route('attendance.store'), $this->rosterPayload($schoolA, [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_ABSENT,
        ]));
        $this->actingAs($schoolB['admin'])->post(route('attendance.store'), $this->rosterPayload($schoolB));

        $this->actingAs($schoolA['teacherUser'])
            ->get(route('attendance.history', ['search' => 'Student A-1']))
            ->assertOk()
            ->assertSee('Student A-1')
            ->assertDontSee('Student B-1');

        $this->actingAs($schoolA['teacherUser'])
            ->get(route('attendance.monthly-summary', [
                'section_id' => $schoolA['section']->id,
                'month' => '2026-06',
            ]))
            ->assertOk()
            ->assertSee('1 recorded day')
            ->assertSee('Present')
            ->assertSee('Absent');

        $this->actingAs($schoolA['admin'])
            ->get(route('attendance.history', ['section_id' => $schoolB['section']->id]))
            ->assertNotFound();
        $this->actingAs($schoolA['teacherUser'])
            ->get(route('attendance.monthly-summary', [
                'section_id' => $schoolB['section']->id,
                'month' => '2026-06',
            ]))
            ->assertNotFound();
    }

    public function test_teacher_history_authorization_queries_remain_bounded_as_rows_increase(): void
    {
        $structure = $this->structure('QUERY', 20);
        $this->actingAs($structure['admin'])
            ->post(route('attendance.store'), $this->rosterPayload($structure))
            ->assertRedirect();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($structure['teacherUser'])
            ->get(route('attendance.history'));
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $response->assertOk()->assertSee('Student QUERY-20');
        $this->assertLessThanOrEqual(43, $queryCount, "Attendance History executed {$queryCount} queries.");
    }

    public function test_cross_tenant_http_write_binding_history_and_summary_paths_are_concealed(): void
    {
        $schoolA = $this->structure('A');
        $schoolB = $this->structure('B');

        $this->actingAs($schoolA['admin'])
            ->post(route('attendance.store'), $this->rosterPayload($schoolA))
            ->assertRedirect();
        $this->actingAs($schoolB['admin'])
            ->post(route('attendance.store'), $this->rosterPayload($schoolB, [
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_LEAVE,
            ]))
            ->assertRedirect();

        $foreignAttendance = Attendance::withoutGlobalScopes()
            ->where('school_id', $schoolB['school']->id)
            ->where('student_id', $schoolB['students'][0]->id)
            ->firstOrFail();

        $this->actingAs($schoolA['admin'])
            ->post(route('attendance.store'), $this->rosterPayload($schoolB))
            ->assertNotFound();
        $this->actingAs($schoolA['admin'])
            ->get(route('attendance.edit', $foreignAttendance))
            ->assertNotFound();
        $this->actingAs($schoolA['admin'])
            ->patch(route('attendance.update', $foreignAttendance), [
                'status' => Attendance::STATUS_PRESENT,
                'remarks' => 'Attempted foreign correction.',
            ])
            ->assertNotFound();

        $this->actingAs($schoolA['admin'])
            ->get(route('attendance.history'))
            ->assertOk()
            ->assertSee('Student A-1')
            ->assertDontSee('Student B-1');
        $this->actingAs($schoolA['admin'])
            ->get(route('attendance.monthly-summary', [
                'section_id' => $schoolB['section']->id,
                'month' => '2026-06',
            ]))
            ->assertNotFound();

        $this->assertDatabaseCount('attendances', 4);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->assertDatabaseCount('audit_logs', 4);
        $this->assertDatabaseHas('attendances', [
            'id' => $foreignAttendance->id,
            'school_id' => $schoolB['school']->id,
            'student_id' => $schoolB['students'][0]->id,
            'status' => Attendance::STATUS_ABSENT,
            'remarks' => null,
            'marked_by' => $schoolB['admin']->id,
        ]);
    }

    public function test_cross_tenant_and_actor_context_mismatch_fail_closed_for_http_and_direct_service(): void
    {
        $schoolA = $this->structure('A');
        $schoolB = $this->structure('B');
        $service = app(AttendanceService::class);

        $this->actingAs($schoolA['admin'])
            ->get(route('attendance.index', [
                'attendance_date' => '2026-06-20',
                'section_id' => $schoolB['section']->id,
            ]))
            ->assertNotFound();

        $this->assertAuthorizationDenied(fn () => $service->workspace($schoolA['admin'], []));
        app(TenantContext::class)->runAsPlatform(
            fn () => $this->assertAuthorizationDenied(fn () => $service->workspace($schoolA['admin'], [])),
        );

        app(TenantContext::class)->runAsTenant($schoolA['school']->id, function () use ($schoolA, $schoolB, $service): void {
            try {
                $service->workspace($schoolA['admin'], ['section_id' => $schoolB['section']->id]);
                $this->fail('Cross-tenant Section access was allowed.');
            } catch (ModelNotFoundException) {
                $this->addToAssertionCount(1);
            }

            $this->assertAuthorizationDenied(fn () => $service->workspace($schoolB['admin'], []));
        });

        $schoolA['admin']->forceFill(['status' => User::STATUS_INACTIVE])->save();
        app(TenantContext::class)->runAsTenant(
            $schoolA['school']->id,
            fn () => $this->assertAuthorizationDenied(fn () => $service->workspace($schoolA['admin'], [])),
        );

        $schoolA['admin']->forceFill(['status' => User::STATUS_ACTIVE])->save();
        $schoolA['school']->forceFill(['status' => School::STATUS_INACTIVE])->save();
        $this->actingAs($schoolA['admin'])->get(route('attendance.index'))->assertRedirect(route('login'));
    }

    public function test_create_update_and_view_permissions_enforce_distinct_attendance_boundaries(): void
    {
        $structure = $this->structure('ONE');
        $teacher = $structure['teacherUser'];
        $role = $teacher->role;
        $create = Permission::query()->where('code', 'attendance.create')->firstOrFail();
        $update = Permission::query()->where('code', 'attendance.update')->firstOrFail();
        $view = Permission::query()->where('code', 'attendance.view')->firstOrFail();

        $role->permissions()->detach($update);

        $this->actingAs($teacher)
            ->post(route('attendance.store'), $this->rosterPayload($structure))
            ->assertRedirect();
        $this->assertDatabaseCount('attendances', 2);

        $changed = $this->rosterPayload($structure, [
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_PRESENT,
        ]);
        $this->actingAs($teacher)->post(route('attendance.store'), $changed)->assertForbidden();
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $structure['students'][0]->id,
            'status' => Attendance::STATUS_ABSENT,
        ]);

        $role->permissions()->detach($create);
        $role->permissions()->syncWithoutDetaching([$update->id]);

        $this->actingAs($teacher)->post(route('attendance.store'), $changed)->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'student_id' => $structure['students'][0]->id,
            'status' => Attendance::STATUS_ABSENT,
        ]);

        $role->permissions()->detach($view);

        $this->actingAs($teacher)->get(route('attendance.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('dashboard'))->assertOk()->assertDontSee('Attendance this month');
    }

    public function test_logging_failure_rolls_back_the_complete_batch(): void
    {
        $structure = $this->structure('ONE');
        $logger = Mockery::mock(SecurityLogService::class);
        $logger->shouldReceive('activity')->once()->andThrow(new RuntimeException('Forced logging failure.'));
        $this->app->instance(SecurityLogService::class, $logger);
        $service = app(AttendanceService::class);

        try {
            app(TenantContext::class)->runAsTenant(
                $structure['school']->id,
                fn () => $service->saveRoster($this->rosterPayload($structure), $structure['admin']),
            );
            $this->fail('The forced logging failure should escape the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced logging failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function structure(string $suffix, int $studentCount = 2): array
    {
        $school = $this->school($suffix);
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, strtolower("admin-{$suffix}@example.com"));
        $teacherUser = $this->schoolUser(Role::TEACHER, $school, strtolower("teacher-{$suffix}@example.com"));

        return app(TenantContext::class)->runAsTenant($school->id, function () use ($admin, $school, $studentCount, $suffix, $teacherUser): array {
            SchoolSetting::create(['timezone' => 'Asia/Kolkata']);
            $teacher = Teacher::create([
                'user_id' => $teacherUser->id,
                'employee_code' => 'EMP-'.$suffix,
                'joining_date' => '2025-04-01',
                'status' => Teacher::STATUS_ACTIVE,
            ]);
            $year = AcademicYear::create([
                'name' => '2026-2027 '.$suffix,
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
            $section = Section::create([
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'name' => 'Section '.$suffix,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $otherSection = Section::create([
                'class_id' => $schoolClass->id,
                'name' => 'Other '.$suffix,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $structure = compact(
                'school',
                'admin',
                'teacherUser',
                'teacher',
                'year',
                'schoolClass',
                'section',
                'otherSection',
            );
            $structure['class'] = $schoolClass;
            $structure['students'] = [];
            $structure['enrollments'] = [];

            for ($index = 1; $index <= $studentCount; $index++) {
                $record = $this->addStudentEnrollment($structure, $suffix.'-'.$index);
                $structure['students'][] = $record['student'];
                $structure['enrollments'][] = $record['enrollment'];
            }

            return $structure;
        });
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return array{student: Student, enrollment: StudentEnrollment}
     */
    private function addStudentEnrollment(
        array $structure,
        string $suffix,
        ?Section $section = null,
        string $enrollmentDate = '2026-04-02',
    ): array {
        $section ??= $structure['section'];
        $student = Student::create([
            'admission_no' => 'ADM-'.$suffix,
            'first_name' => 'Student',
            'last_name' => $suffix,
            'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
            'date_of_birth' => '2015-05-10',
            'guardian_name' => 'Guardian '.$suffix,
            'guardian_phone' => '9876500000',
            'admission_date' => '2026-04-02',
            'status' => Student::STATUS_ACTIVE,
        ]);
        $enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $structure['year']->id,
            'class_id' => $structure['class']->id,
            'section_id' => $section->id,
            'roll_no' => 'ROLL-'.$suffix,
            'enrollment_date' => $enrollmentDate,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ]);

        return compact('student', 'enrollment');
    }

    /**
     * @param  array<string, mixed>  $structure
     * @param  list<string>  $statuses
     * @return array<string, mixed>
     */
    private function rosterPayload(array $structure, array $statuses = []): array
    {
        return [
            'attendance_date' => '2026-06-20',
            'section_id' => $structure['section']->id,
            'mode' => 'roster',
            'entries' => collect($structure['students'])->values()->map(function (Student $student, int $index) use ($statuses): array {
                return [
                    'student_id' => $student->id,
                    'status' => $statuses[$index] ?? Attendance::STATUS_PRESENT,
                    'remarks' => null,
                ];
            })->all(),
        ];
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'ATT-'.$suffix,
            'email' => strtolower("attendance-{$suffix}@school.example.com"),
            'status' => School::STATUS_ACTIVE,
        ]);
    }

    private function schoolUser(string $roleCode, School $school, string $email): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'school_id' => $school->id,
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function assertAuthorizationDenied(callable $callback): void
    {
        try {
            $callback();
            $this->fail('An unauthorized Attendance service path was allowed.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }
}
