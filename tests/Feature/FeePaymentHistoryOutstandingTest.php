<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\FeeCollectionService;
use App\Services\FeeOutstandingBalanceService;
use App\Services\FeePaymentHistoryService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeePaymentHistoryOutstandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_payment_history_and_outstanding_balance_routes(): void
    {
        $school = $this->school('One');
        $studentFee = $this->assignedStudentFee($school);
        $payment = $this->collectViaService($school, $studentFee, '500.00');

        foreach ([
            fn () => $this->get(route('fee-payments.index')),
            fn () => $this->get(route('fee-outstanding-balances.index')),
            fn () => $this->get(route('fee-payments.show', $payment)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_teacher_and_super_admin_are_denied_payment_history_and_outstanding_balances(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $studentFee = $this->assignedStudentFee($school);
        $payment = $this->collectViaService($school, $studentFee, '500.00');

        $this->actingAs($this->superAdmin())->get(route('fee-payments.index'))->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('fee-outstanding-balances.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-payments.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-outstanding-balances.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-payments.show', $payment))->assertForbidden();
    }

    public function test_accountant_can_review_payment_history_and_outstanding_balances_but_not_setup(): void
    {
        $school = $this->school('One');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $studentFee = $this->assignedStudentFee($school, '1400.00');
        $payment = $this->collectViaService($school, $studentFee, '400.00');

        $this->actingAs($accountant)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('student-fees.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('student-fees.show', $studentFee))->assertForbidden();
        $this->actingAs($accountant)->get(route('fee-payments.index'))->assertOk()->assertSee($payment->receipt_no);
        $this->actingAs($accountant)->get(route('fee-outstanding-balances.index'))->assertOk()->assertSee('1,000.00');
    }

    public function test_payment_history_filters_by_search_mode_and_date_range(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $studentFee = $this->assignedStudentFee($school, '1400.00');
        $cashPayment = $this->collectViaService($school, $studentFee, '300.00');

        $this->tenant($school, function () use ($admin, $studentFee, $cashPayment): void {
            $service = app(FeeCollectionService::class);
            $upiPayment = $service->collect($studentFee->fresh(), [
                'amount_paid' => '200.00',
                'payment_date' => now()->subDays(5)->toDateString(),
                'payment_mode' => FeePayment::MODE_UPI,
                'collection_token' => $service->issueCollectionToken($studentFee->fresh(), $admin),
            ], $admin);
            $upiPayment->forceFill(['payment_date' => now()->subDays(5)->toDateString()])->save();

            $this->actingAs($admin)->get(route('fee-payments.index', [
                'search' => $cashPayment->receipt_no,
            ]))->assertOk()->assertSee($cashPayment->receipt_no)->assertDontSee($upiPayment->receipt_no);

            $this->actingAs($admin)->get(route('fee-payments.index', [
                'payment_mode' => FeePayment::MODE_UPI,
            ]))->assertOk()->assertSee($upiPayment->receipt_no)->assertDontSee($cashPayment->receipt_no);

            $this->actingAs($admin)->get(route('fee-payments.index', [
                'date_from' => now()->subDays(6)->toDateString(),
                'date_to' => now()->subDays(4)->toDateString(),
            ]))->assertOk()->assertSee($upiPayment->receipt_no)->assertDontSee($cashPayment->receipt_no);
        });
    }

    public function test_outstanding_balance_filters_and_summary_totals(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $pendingFee = $this->assignedStudentFee($school, '1000.00', 'Pending');
        $partialFee = $this->assignedStudentFee($school, '800.00', 'Partial');
        $this->collectViaService($school, $partialFee, '300.00');
        $partialAdmissionNo = $this->tenant($school, fn () => $partialFee->fresh()->student->admission_no);

        $response = $this->actingAs($admin)->get(route('fee-outstanding-balances.index'));
        $response->assertOk()
            ->assertSee('1,000.00')
            ->assertSee('500.00')
            ->assertSee('1500.00');

        $this->actingAs($admin)->get(route('fee-outstanding-balances.index', [
            'state' => StudentFee::STATUS_PARTIAL,
        ]))->assertOk()->assertSee('500.00')->assertDontSee('1,000.00');

        $this->actingAs($admin)->get(route('fee-outstanding-balances.index', [
            'search' => $this->tenant($school, fn () => $pendingFee->fresh()->student->admission_no),
        ]))->assertOk()->assertSee('1,000.00')->assertDontSee($partialAdmissionNo);
    }

    public function test_cross_tenant_payment_history_and_outstanding_balances_are_not_exposed(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $studentFeeA = $this->assignedStudentFee($schoolA, '900.00');
        $admissionNo = $this->tenant($schoolA, fn () => $studentFeeA->fresh()->student->admission_no);
        $paymentA = $this->collectViaService($schoolA, $studentFeeA, '100.00');

        $this->actingAs($adminB)->get(route('fee-payments.index'))->assertOk()->assertDontSee($paymentA->receipt_no);
        $this->actingAs($adminB)->get(route('fee-payments.show', $paymentA))->assertNotFound();
        $this->actingAs($adminB)->get(route('fee-outstanding-balances.index'))->assertOk()->assertDontSee($admissionNo);
    }

    public function test_direct_payment_history_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->assignedStudentFee($school);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(FeePaymentHistoryService::class)->listFor($admin, []));
    }

    public function test_direct_outstanding_balance_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->assignedStudentFee($school);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(FeeOutstandingBalanceService::class)->listFor($admin, []));
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'FEE-HIST-'.$suffix,
            'email' => strtolower('fee-hist-'.$suffix).'@school.example.com',
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

    private function assignedStudentFee(School $school, string $payable = '1400.00', string $label = 'Default'): StudentFee
    {
        $suffix = str($label)->slug('-').'-'.uniqid();

        return $this->tenant($school, function () use ($payable, $suffix): StudentFee {
            $category = FeeCategory::create([
                'name' => 'Tuition '.$suffix,
                'description' => 'Annual tuition',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
            $year = AcademicYear::create([
                'name' => '2026-Hist-'.$suffix,
                'start_date' => now()->subMonths(2)->toDateString(),
                'end_date' => now()->addMonths(8)->toDateString(),
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class 8 '.$suffix,
                'code' => 'VIII-'.$suffix,
                'sort_order' => 8,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $section = Section::create([
                'class_id' => $class->id,
                'name' => 'A',
                'capacity' => 30,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $structure = FeeStructure::create([
                'fee_category_id' => $category->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'amount' => $payable,
                'due_date' => '2026-08-01',
                'frequency' => FeeStructure::FREQUENCY_ANNUAL,
                'status' => FeeStructure::STATUS_ACTIVE,
            ]);
            $student = Student::create([
                'admission_no' => 'ADM-HIST-'.$suffix,
                'first_name' => 'History',
                'last_name' => 'Student',
                'gender' => Student::GENDER_FEMALE,
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'guardian_name' => 'Guardian History',
                'guardian_phone' => '9876500002',
                'guardian_email' => 'guardian-hist@example.test',
                'admission_date' => now()->subMonth()->toDateString(),
                'status' => Student::STATUS_ACTIVE,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-HIST',
                'enrollment_date' => now()->toDateString(),
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ]);

            return StudentFee::create([
                'student_id' => $student->id,
                'fee_structure_id' => $structure->id,
                'academic_year_id' => $year->id,
                'amount' => $payable,
                'discount_amount' => '0.00',
                'payable_amount' => $payable,
                'paid_amount' => '0.00',
                'balance_amount' => $payable,
                'status' => StudentFee::STATUS_PENDING,
            ]);
        });
    }

    private function collectViaService(School $school, StudentFee $studentFee, string $amount): FeePayment
    {
        return $this->tenant($school, function () use ($school, $studentFee, $amount): FeePayment {
            $admin = User::query()->where('school_id', $school->id)->whereHas('role', fn ($q) => $q->where('code', Role::SCHOOL_ADMIN))->first()
                ?? $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'hist-admin-'.$school->id.'@example.com');
            $service = app(FeeCollectionService::class);
            $token = $service->issueCollectionToken($studentFee, $admin);

            return $service->collect($studentFee, [
                'amount_paid' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_mode' => FeePayment::MODE_CASH,
                'collection_token' => $token,
            ], $admin);
        });
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
