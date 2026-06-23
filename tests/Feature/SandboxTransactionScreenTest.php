<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\FeeCollectionService;
use App\Services\SandboxTransactionService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SandboxTransactionScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_sandbox_transaction_routes(): void
    {
        $school = $this->school('One');
        $transaction = $this->sandboxTransaction($school);

        foreach ([
            fn () => $this->get(route('payment-transactions.index')),
            fn () => $this->get(route('payment-transactions.show', $transaction)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_teacher_and_super_admin_are_denied_sandbox_transaction_screens(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $transaction = $this->sandboxTransaction($school);

        $this->actingAs($this->superAdmin())->get(route('payment-transactions.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('payment-transactions.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('payment-transactions.show', $transaction))->assertForbidden();
    }

    public function test_accountant_can_review_sandbox_transactions_but_not_setup(): void
    {
        $school = $this->school('One');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $transaction = $this->sandboxTransaction($school);

        $this->actingAs($accountant)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('payment-transactions.index'))->assertOk()->assertSee($transaction->transaction_no);
        $this->actingAs($accountant)->get(route('payment-transactions.show', $transaction))->assertOk()->assertSee('local_sandbox');
    }

    public function test_sandbox_transaction_list_excludes_non_sandbox_records_and_supports_filters(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $sandbox = $this->sandboxTransaction($school, '700.00');
        $cashStudentFee = $this->assignedStudentFee($school, '500.00');
        $this->collectViaService($school, $cashStudentFee, '500.00', FeePayment::MODE_CASH);

        $this->actingAs($admin)->get(route('payment-transactions.index'))
            ->assertOk()
            ->assertSee($sandbox->transaction_no)
            ->assertDontSee('500.00');

        $this->actingAs($admin)->get(route('payment-transactions.index', [
            'search' => $sandbox->transaction_no,
        ]))->assertOk()->assertSee($sandbox->transaction_no);

        $this->actingAs($admin)->get(route('payment-transactions.index', [
            'status' => PaymentTransaction::STATUS_COMPLETED,
        ]))->assertOk()->assertSee($sandbox->transaction_no);
    }

    public function test_non_sandbox_payment_transaction_show_returns_not_found(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $cashStudentFee = $this->assignedStudentFee($school, '500.00');
        $payment = $this->collectViaService($school, $cashStudentFee, '500.00', FeePayment::MODE_CASH);
        $transaction = PaymentTransaction::query()->withoutGlobalScopes()->where('fee_payment_id', $payment->id)->firstOrFail();

        $this->actingAs($admin)->get(route('payment-transactions.show', $transaction))->assertForbidden();
    }

    public function test_cross_tenant_sandbox_transactions_are_not_exposed(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $transactionA = $this->sandboxTransaction($schoolA);

        $this->actingAs($adminB)->get(route('payment-transactions.index'))->assertOk()->assertDontSee($transactionA->transaction_no);
        $this->actingAs($adminB)->get(route('payment-transactions.show', $transactionA))->assertNotFound();
    }

    public function test_direct_sandbox_transaction_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $this->sandboxTransaction($school);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(SandboxTransactionService::class)->listFor($admin, []));
    }

    public function test_direct_sandbox_transaction_show_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $transaction = $this->sandboxTransaction($school);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(SandboxTransactionService::class)->showFor($transaction, $admin));
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'FEE-SBOX-'.$suffix,
            'email' => strtolower('fee-sbox-'.$suffix).'@school.example.com',
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

    private function assignedStudentFee(School $school, string $payable = '1400.00'): StudentFee
    {
        $suffix = uniqid();

        return $this->tenant($school, function () use ($payable, $suffix): StudentFee {
            $category = FeeCategory::create([
                'name' => 'Tuition '.$suffix,
                'description' => 'Annual tuition',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
            $year = AcademicYear::create([
                'name' => '2026-Sbox-'.$suffix,
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
                'admission_no' => 'ADM-SBOX-'.$suffix,
                'first_name' => 'Sandbox',
                'last_name' => 'Student',
                'gender' => Student::GENDER_FEMALE,
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'guardian_name' => 'Guardian Sandbox',
                'guardian_phone' => '9876500003',
                'guardian_email' => 'guardian-sbox@example.test',
                'admission_date' => now()->subMonth()->toDateString(),
                'status' => Student::STATUS_ACTIVE,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-SBOX',
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

    private function sandboxTransaction(School $school, string $amount = '600.00'): PaymentTransaction
    {
        $studentFee = $this->assignedStudentFee($school, $amount);
        $payment = $this->collectViaService($school, $studentFee, $amount, FeePayment::MODE_SANDBOX_GATEWAY);

        return PaymentTransaction::query()->withoutGlobalScopes()->where('fee_payment_id', $payment->id)->firstOrFail();
    }

    private function collectViaService(
        School $school,
        StudentFee $studentFee,
        string $amount,
        string $mode = FeePayment::MODE_CASH,
    ): FeePayment {
        return $this->tenant($school, function () use ($school, $studentFee, $amount, $mode): FeePayment {
            $admin = User::query()->where('school_id', $school->id)->whereHas('role', fn ($q) => $q->where('code', Role::SCHOOL_ADMIN))->first()
                ?? $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'sbox-admin-'.$school->id.'@example.com');
            $service = app(FeeCollectionService::class);
            $token = $service->issueCollectionToken($studentFee, $admin);

            return $service->collect($studentFee, [
                'amount_paid' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_mode' => $mode,
                'collection_token' => $token,
            ], $admin);
        });
    }

    private function tenant(School $school, callable $callback): mixed
    {
        return app(TenantContext::class)->runAsTenant($school->id, $callback);
    }
}
