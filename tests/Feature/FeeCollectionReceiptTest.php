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
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeeCollectionReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_are_redirected_from_fee_collection_and_receipt_routes(): void
    {
        $school = $this->school('One');
        $studentFee = $this->assignedStudentFee($school);
        $payment = $this->collectViaService($school, $studentFee, '500.00');

        foreach ([
            fn () => $this->get(route('fee-collections.index')),
            fn () => $this->get(route('fee-collections.create', $studentFee)),
            fn () => $this->post(route('fee-collections.store', $studentFee), []),
            fn () => $this->get(route('fee-payments.show', $payment)),
            fn () => $this->get(route('fee-payments.print', $payment)),
        ] as $request) {
            $request()->assertRedirect(route('login'));
        }
    }

    public function test_teacher_and_super_admin_are_denied_fee_collection_and_receipts(): void
    {
        $school = $this->school('One');
        $teacher = $this->schoolUser(Role::TEACHER, $school, 'teacher@example.com');
        $studentFee = $this->assignedStudentFee($school);
        $payment = $this->collectViaService($school, $studentFee, '500.00');

        $this->actingAs($this->superAdmin())->get(route('fee-collections.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-collections.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('fee-payments.show', $payment))->assertForbidden();
    }

    public function test_accountant_can_collect_but_not_manage_fee_setup(): void
    {
        $school = $this->school('One');
        $accountant = $this->schoolUser(Role::ACCOUNTANT, $school, 'accountant@example.com');
        $studentFee = $this->assignedStudentFee($school, '1400.00');

        $this->actingAs($accountant)->get(route('fee-categories.index'))->assertForbidden();
        $this->actingAs($accountant)->get(route('student-fees.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('fee-collections.index'))->assertOk();

        $payment = $this->collectThroughHttp($accountant, $studentFee, [
            'amount_paid' => '400.00',
            'payment_mode' => FeePayment::MODE_CASH,
        ]);

        $this->assertDatabaseHas('student_fees', [
            'id' => $studentFee->id,
            'paid_amount' => '400.00',
            'balance_amount' => '1000.00',
            'status' => StudentFee::STATUS_PARTIAL,
        ]);
        $this->assertDatabaseHas('fee_payments', [
            'id' => $payment->id,
            'amount_paid' => '400.00',
            'received_by' => $accountant->id,
        ]);
    }

    public function test_school_admin_collects_full_payment_with_receipt_transaction_and_logs(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $studentFee = $this->assignedStudentFee($school, '1400.00');

        $payment = $this->collectThroughHttp($admin, $studentFee, [
            'amount_paid' => '1400.00',
            'payment_mode' => FeePayment::MODE_UPI,
            'remarks' => 'Term payment',
        ]);

        $this->assertDatabaseHas('student_fees', [
            'id' => $studentFee->id,
            'paid_amount' => '1400.00',
            'balance_amount' => '0.00',
            'status' => StudentFee::STATUS_PAID,
        ]);
        $this->assertDatabaseHas('fee_payments', [
            'id' => $payment->id,
            'student_fee_id' => $studentFee->id,
            'amount_paid' => '1400.00',
            'payment_mode' => FeePayment::MODE_UPI,
            'status' => FeePayment::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('payment_transactions', [
            'fee_payment_id' => $payment->id,
            'amount' => '1400.00',
            'payment_mode' => FeePayment::MODE_UPI,
            'status' => PaymentTransaction::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $school->id,
            'module' => 'fee_management',
            'action' => 'collected',
            'subject_type' => FeePayment::class,
            'subject_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'auditable_type' => StudentFee::class,
            'auditable_id' => $studentFee->id,
            'event' => 'collected',
        ]);
    }

    public function test_collection_rejects_overpayment_future_dates_prohibited_fields_and_invalid_tokens(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $studentFee = $this->assignedStudentFee($school, '1400.00');
        $token = $this->collectionToken($admin, $studentFee);

        $this->actingAs($admin)->post(route('fee-collections.store', $studentFee), [
            'amount_paid' => '1500.00',
            'payment_date' => now()->toDateString(),
            'payment_mode' => FeePayment::MODE_CASH,
            'collection_token' => $token,
        ])->assertSessionHasErrors(['amount_paid']);

        $token = $this->collectionToken($admin, $studentFee);
        $this->actingAs($admin)->post(route('fee-collections.store', $studentFee), [
            'amount_paid' => '100.00',
            'payment_date' => now()->addDay()->toDateString(),
            'payment_mode' => FeePayment::MODE_CASH,
            'collection_token' => $token,
        ])->assertSessionHasErrors(['payment_date']);

        $token = $this->collectionToken($admin, $studentFee);
        $this->actingAs($admin)->post(route('fee-collections.store', $studentFee), [
            'amount_paid' => '100.00',
            'payment_date' => now()->toDateString(),
            'payment_mode' => FeePayment::MODE_CASH,
            'collection_token' => $token,
            'receipt_no' => 'FORGED',
        ])->assertSessionHasErrors(['receipt_no']);

        $this->actingAs($admin)->post(route('fee-collections.store', $studentFee), [
            'amount_paid' => '100.00',
            'payment_date' => now()->toDateString(),
            'payment_mode' => FeePayment::MODE_CASH,
            'collection_token' => '00000000-0000-0000-0000-000000000000',
        ])->assertSessionHasErrors(['collection_token']);
    }

    public function test_cross_tenant_fee_payments_are_not_exposed(): void
    {
        $schoolA = $this->school('A');
        $schoolB = $this->school('B');
        $adminB = $this->schoolUser(Role::SCHOOL_ADMIN, $schoolB, 'admin-b@example.com');
        $studentFeeA = $this->assignedStudentFee($schoolA);
        $paymentA = $this->collectViaService($schoolA, $studentFeeA, '500.00');

        $this->actingAs($adminB)->get(route('fee-collections.create', $studentFeeA))->assertNotFound();
        $this->actingAs($adminB)->get(route('fee-payments.show', $paymentA))->assertNotFound();
        $this->actingAs($adminB)->get(route('fee-payments.print', $paymentA))->assertNotFound();
    }

    public function test_direct_collection_service_rejects_wrong_tenant_context(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $studentFee = $this->assignedStudentFee($school);

        $this->expectException(AuthorizationException::class);
        app(TenantContext::class)->runAsPlatform(fn () => app(FeeCollectionService::class)->collect(
            $studentFee,
            [
                'amount_paid' => '100.00',
                'payment_date' => now()->toDateString(),
                'payment_mode' => FeePayment::MODE_CASH,
                'collection_token' => '00000000-0000-0000-0000-000000000000',
            ],
            $admin,
        ));
    }

    public function test_sandbox_gateway_collection_stores_sanitized_transaction_payload(): void
    {
        $school = $this->school('One');
        $admin = $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'admin@example.com');
        $studentFee = $this->assignedStudentFee($school, '600.00');

        $payment = $this->collectThroughHttp($admin, $studentFee, [
            'amount_paid' => '600.00',
            'payment_mode' => FeePayment::MODE_SANDBOX_GATEWAY,
        ]);

        $transaction = PaymentTransaction::query()->withoutGlobalScopes()->where('fee_payment_id', $payment->id)->firstOrFail();
        $this->assertSame(FeePayment::MODE_SANDBOX_GATEWAY, $transaction->payment_mode);
        $this->assertSame([
            'gateway' => 'local_sandbox',
            'reference' => $transaction->gateway_reference,
            'amount' => '600.00',
            'status' => PaymentTransaction::STATUS_COMPLETED,
        ], collect($transaction->raw_response)->only(['gateway', 'reference', 'amount', 'status'])->all());
        $this->assertArrayHasKey('processed_at', $transaction->raw_response ?? []);
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'FEE-COLLECT-'.$suffix,
            'email' => strtolower('fee-collect-'.$suffix).'@school.example.com',
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
        return $this->tenant($school, function () use ($payable): StudentFee {
            $category = FeeCategory::create([
                'name' => 'Tuition',
                'description' => 'Annual tuition',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]);
            $year = AcademicYear::create([
                'name' => '2026-Collect',
                'start_date' => now()->subMonths(2)->toDateString(),
                'end_date' => now()->addMonths(8)->toDateString(),
                'is_current' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ]);
            $class = SchoolClass::create([
                'name' => 'Class 8',
                'code' => 'VIII-C',
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
                'admission_no' => 'ADM-COL-'.$structure->id,
                'first_name' => 'Collect',
                'last_name' => 'Student',
                'gender' => Student::GENDER_FEMALE,
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'guardian_name' => 'Guardian Collect',
                'guardian_phone' => '9876500001',
                'guardian_email' => 'guardian-collect@example.test',
                'admission_date' => now()->subMonth()->toDateString(),
                'status' => Student::STATUS_ACTIVE,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'roll_no' => 'ROLL-COL',
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function collectThroughHttp(User $actor, StudentFee $studentFee, array $overrides = []): FeePayment
    {
        $token = $this->collectionToken($actor, $studentFee);

        $response = $this->actingAs($actor)->post(route('fee-collections.store', $studentFee), [
            'amount_paid' => '100.00',
            'payment_date' => now()->toDateString(),
            'payment_mode' => FeePayment::MODE_CASH,
            'collection_token' => $token,
            ...$overrides,
        ]);

        $paymentId = (int) DB::table('fee_payments')->orderByDesc('id')->value('id');
        $response->assertRedirect(route('fee-payments.show', $paymentId));

        return FeePayment::query()->withoutGlobalScopes()->findOrFail($paymentId);
    }

    private function collectionToken(User $actor, StudentFee $studentFee): string
    {
        $this->actingAs($actor)->get(route('fee-collections.create', $studentFee))->assertOk();

        return (string) session('fee_collection_token_'.$studentFee->id);
    }

    private function collectViaService(School $school, StudentFee $studentFee, string $amount): FeePayment
    {
        return $this->tenant($school, function () use ($school, $studentFee, $amount): FeePayment {
            $admin = User::query()->where('school_id', $school->id)->whereHas('role', fn ($q) => $q->where('code', Role::SCHOOL_ADMIN))->first()
                ?? $this->schoolUser(Role::SCHOOL_ADMIN, $school, 'collect-admin-'.$school->id.'@example.com');
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
