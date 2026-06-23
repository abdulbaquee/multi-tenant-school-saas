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
use App\Tenancy\TenantContext;
use App\Tenancy\TenantContextException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class FeeSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_migration_defines_documented_fee_tables_columns_indexes_and_retention_boundaries(): void
    {
        $expectedColumns = [
            'fee_categories' => [
                'id', 'school_id', 'name', 'description', 'status',
                'created_at', 'updated_at', 'deleted_at',
            ],
            'fee_structures' => [
                'id', 'school_id', 'fee_category_id', 'academic_year_id',
                'class_id', 'amount', 'due_date', 'frequency', 'status',
                'created_at', 'updated_at', 'deleted_at',
            ],
            'student_fees' => [
                'id', 'school_id', 'student_id', 'fee_structure_id',
                'academic_year_id', 'amount', 'discount_amount',
                'payable_amount', 'paid_amount', 'balance_amount', 'due_date',
                'status', 'created_at', 'updated_at', 'deleted_at',
            ],
            'fee_payments' => [
                'id', 'school_id', 'student_fee_id', 'student_id',
                'receipt_no', 'amount_paid', 'payment_date', 'payment_mode',
                'status', 'received_by', 'remarks', 'created_at', 'updated_at',
            ],
            'payment_transactions' => [
                'id', 'school_id', 'fee_payment_id', 'transaction_no',
                'gateway_reference', 'amount', 'payment_mode', 'status',
                'processed_at', 'raw_response', 'created_at', 'updated_at',
            ],
        ];

        foreach ($expectedColumns as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumns($table, $columns));
        }

        foreach (['fee_payments', 'payment_transactions'] as $retainedTable) {
            $this->assertFalse(Schema::hasColumn($retainedTable, 'deleted_at'));
        }

        $expectedIndexes = [
            'fee_categories' => [
                'idx_fee_categories_school_id',
                'idx_fee_categories_name',
                'idx_fee_categories_status',
                'idx_fee_categories_deleted_at',
                'uq_fee_categories_school_name',
            ],
            'fee_structures' => [
                'idx_fee_structures_school_id',
                'idx_fee_structures_fee_category_id',
                'idx_fee_structures_academic_year_id',
                'idx_fee_structures_class_id',
                'idx_fee_structures_due_date',
                'idx_fee_structures_frequency',
                'idx_fee_structures_status',
                'idx_fee_structures_deleted_at',
                'uq_fee_structures_scope',
            ],
            'student_fees' => [
                'idx_student_fees_school_id',
                'idx_student_fees_student_id',
                'idx_student_fees_fee_structure_id',
                'idx_student_fees_academic_year_id',
                'idx_student_fees_balance_amount',
                'idx_student_fees_due_date',
                'idx_student_fees_status',
                'idx_student_fees_deleted_at',
                'uq_student_fees_student_structure',
            ],
            'fee_payments' => [
                'idx_fee_payments_school_id',
                'idx_fee_payments_student_fee_id',
                'idx_fee_payments_student_id',
                'idx_fee_payments_receipt_no',
                'idx_fee_payments_payment_date',
                'idx_fee_payments_payment_mode',
                'idx_fee_payments_status',
                'idx_fee_payments_received_by',
                'uq_fee_payments_school_receipt_no',
            ],
            'payment_transactions' => [
                'idx_payment_transactions_school_id',
                'idx_payment_transactions_fee_payment_id',
                'idx_payment_transactions_transaction_no',
                'idx_payment_transactions_gateway_reference',
                'idx_payment_transactions_payment_mode',
                'idx_payment_transactions_status',
                'idx_payment_transactions_processed_at',
                'uq_payment_transactions_school_transaction_no',
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            $actualIndexes = collect(Schema::getIndexes($table))->pluck('name')->all();

            foreach ($indexes as $index) {
                $this->assertContains($index, $actualIndexes, "Missing {$index} on {$table}.");
            }
        }
    }

    public function test_fee_models_default_deny_and_require_tenant_context_for_creation(): void
    {
        $school = $this->school('One');
        $graph = $this->createFeeGraph($school, 'ONE');

        $this->assertSame(0, FeeCategory::query()->count());
        $this->assertTenantCreateDenied(fn () => FeeCategory::create($this->feeCategoryPayload()));
        $this->assertTenantCreateDenied(fn () => FeeStructure::create($this->feeStructurePayload($graph)));
        $this->assertTenantCreateDenied(fn () => StudentFee::create($this->studentFeePayload($graph)));
        $this->assertTenantCreateDenied(fn () => FeePayment::create($this->feePaymentPayload($graph)));
        $this->assertTenantCreateDenied(fn () => PaymentTransaction::create($this->paymentTransactionPayload($graph)));

        app(TenantContext::class)->runAsPlatform(function () use ($graph): void {
            $this->assertTenantCreateDenied(fn () => FeeCategory::create($this->feeCategoryPayload()));
            $this->assertTenantCreateDenied(fn () => FeeStructure::create($this->feeStructurePayload($graph)));
            $this->assertTenantCreateDenied(fn () => StudentFee::create($this->studentFeePayload($graph)));
            $this->assertTenantCreateDenied(fn () => FeePayment::create($this->feePaymentPayload($graph)));
            $this->assertTenantCreateDenied(fn () => PaymentTransaction::create($this->paymentTransactionPayload($graph)));
        });
    }

    public function test_tenant_context_assigns_ownership_filters_fee_records_and_platform_reads_all(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $graphOne = $this->createFeeGraph($schoolOne, 'ONE', $schoolTwo->id);
        $graphTwo = $this->createFeeGraph($schoolTwo, 'TWO', $schoolOne->id);
        $context = app(TenantContext::class);

        foreach (['category', 'structure', 'studentFee', 'payment', 'transaction'] as $key) {
            $this->assertSame($schoolOne->id, $graphOne[$key]->school_id);
            $this->assertSame($schoolTwo->id, $graphTwo[$key]->school_id);
        }

        $models = [
            FeeCategory::class => 'category',
            FeeStructure::class => 'structure',
            StudentFee::class => 'studentFee',
            FeePayment::class => 'payment',
            PaymentTransaction::class => 'transaction',
        ];

        $context->setTenant($schoolOne->id);
        foreach ($models as $model => $key) {
            $this->assertSame([$graphOne[$key]->id], $model::query()->pluck('id')->all());
        }

        $context->setTenant($schoolTwo->id);
        foreach ($models as $model => $key) {
            $this->assertSame([$graphTwo[$key]->id], $model::query()->pluck('id')->all());
        }

        $context->setPlatform();
        foreach (array_keys($models) as $model) {
            $this->assertSame(2, $model::query()->count());
        }

        $context->clear();
        foreach (array_keys($models) as $model) {
            $this->assertSame(0, $model::query()->count());
        }
    }

    public function test_fee_scope_tenant_ownership_and_financial_history_are_immutable(): void
    {
        $schoolOne = $this->school('One');
        $schoolTwo = $this->school('Two');
        $graph = $this->createFeeGraph($schoolOne, 'ONE');

        app(TenantContext::class)->runAsTenant($schoolOne->id, function () use ($schoolTwo, $graph): void {
            $structure = $graph['structure']->fresh();
            $structure->amount = '1250.00';
            $structure->save();
            $this->assertSame('1250.00', $structure->fresh()->amount);

            foreach (['fee_category_id', 'academic_year_id', 'class_id'] as $field) {
                $structure = $structure->fresh();
                $structure->{$field} = 999999;

                try {
                    $structure->save();
                    $this->fail("Fee Structure {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Fee Structure scope cannot be changed.', $exception->getMessage());
                }
            }

            $studentFee = $graph['studentFee']->fresh();
            $studentFee->paid_amount = '100.00';
            $studentFee->balance_amount = '850.00';
            $studentFee->status = StudentFee::STATUS_PARTIAL;
            $studentFee->save();
            $this->assertSame('100.00', $studentFee->fresh()->paid_amount);

            foreach (['student_id', 'fee_structure_id', 'academic_year_id'] as $field) {
                $studentFee = $studentFee->fresh();
                $studentFee->{$field} = 999999;

                try {
                    $studentFee->save();
                    $this->fail("Student Fee {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Student Fee assignment scope cannot be changed.', $exception->getMessage());
                }
            }

            $payment = $graph['payment']->fresh();
            $payment->status = FeePayment::STATUS_REVERSED;
            $payment->remarks = 'Reversal status recorded.';
            $payment->save();
            $this->assertSame(FeePayment::STATUS_REVERSED, $payment->fresh()->status);

            foreach (['student_fee_id', 'student_id', 'receipt_no', 'amount_paid', 'payment_date', 'payment_mode', 'received_by'] as $field) {
                $payment = $payment->fresh();
                $payment->{$field} = $field === 'receipt_no' ? 'FEE-CHANGED' : 999999;

                try {
                    $payment->save();
                    $this->fail("Fee Payment {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Fee Payment financial history cannot be changed.', $exception->getMessage());
                }
            }

            $transaction = $graph['transaction']->fresh();
            $transaction->status = PaymentTransaction::STATUS_REVERSED;
            $transaction->save();
            $this->assertSame(PaymentTransaction::STATUS_REVERSED, $transaction->fresh()->status);

            foreach (['fee_payment_id', 'transaction_no', 'gateway_reference', 'amount', 'payment_mode', 'processed_at', 'raw_response'] as $field) {
                $transaction = $transaction->fresh();
                $transaction->{$field} = match ($field) {
                    'transaction_no' => 'TXN-CHANGED',
                    'gateway_reference' => 'GW-CHANGED',
                    'processed_at' => '2026-06-24 10:00:00',
                    'raw_response' => ['gateway' => 'changed'],
                    default => 999999,
                };

                try {
                    $transaction->save();
                    $this->fail("Payment Transaction {$field} was changed.");
                } catch (LogicException $exception) {
                    $this->assertSame('Payment Transaction financial history cannot be changed.', $exception->getMessage());
                }
            }

            foreach ([FeeCategory::class, FeeStructure::class, StudentFee::class, FeePayment::class, PaymentTransaction::class] as $model) {
                $record = match ($model) {
                    FeeCategory::class => $graph['category']->fresh(),
                    FeeStructure::class => $graph['structure']->fresh(),
                    StudentFee::class => $graph['studentFee']->fresh(),
                    FeePayment::class => $graph['payment']->fresh(),
                    PaymentTransaction::class => $graph['transaction']->fresh(),
                };
                $record->school_id = $schoolTwo->id;

                try {
                    $record->save();
                    $this->fail("{$model} tenant ownership was changed.");
                } catch (TenantContextException $exception) {
                    $this->assertStringContainsString('ownership cannot be changed', $exception->getMessage());
                }
            }

            try {
                $graph['payment']->fresh()->delete();
                $this->fail('A retained Fee Payment was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Fee Payment records are retained and cannot be deleted.', $exception->getMessage());
            }

            try {
                $graph['transaction']->fresh()->delete();
                $this->fail('A retained Payment Transaction was deleted.');
            } catch (LogicException $exception) {
                $this->assertSame('Payment Transaction records are retained and cannot be deleted.', $exception->getMessage());
            }
        });
    }

    public function test_relationships_casts_fillable_statuses_modes_and_retained_parents_match_design(): void
    {
        $school = $this->school('One');
        $graph = $this->createFeeGraph($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $graph): void {
            $category = $graph['category']->fresh();
            $structure = $graph['structure']->fresh();
            $studentFee = $graph['studentFee']->fresh();
            $payment = $graph['payment']->fresh();
            $transaction = $graph['transaction']->fresh();

            $this->assertTrue($category->school->is($school));
            $this->assertTrue($category->feeStructures->first()->is($structure));
            $this->assertTrue($structure->school->is($school));
            $this->assertTrue($structure->feeCategory->is($category));
            $this->assertTrue($structure->academicYear->is($graph['year']));
            $this->assertTrue($structure->schoolClass->is($graph['class']));
            $this->assertTrue($structure->studentFees->first()->is($studentFee));
            $this->assertTrue($studentFee->school->is($school));
            $this->assertTrue($studentFee->student->is($graph['student']));
            $this->assertTrue($studentFee->feeStructure->is($structure));
            $this->assertTrue($studentFee->academicYear->is($graph['year']));
            $this->assertTrue($studentFee->feePayments->first()->is($payment));
            $this->assertTrue($payment->school->is($school));
            $this->assertTrue($payment->studentFee->is($studentFee));
            $this->assertTrue($payment->student->is($graph['student']));
            $this->assertTrue($payment->receivedBy->is($graph['collector']));
            $this->assertTrue($payment->paymentTransactions->first()->is($transaction));
            $this->assertTrue($transaction->school->is($school));
            $this->assertTrue($transaction->feePayment->is($payment));

            $this->assertTrue($school->feeCategories->first()->is($category));
            $this->assertTrue($school->feeStructures->first()->is($structure));
            $this->assertTrue($school->studentFees->first()->is($studentFee));
            $this->assertTrue($school->feePayments->first()->is($payment));
            $this->assertTrue($school->paymentTransactions->first()->is($transaction));
            $this->assertTrue($graph['year']->feeStructures->first()->is($structure));
            $this->assertTrue($graph['year']->studentFees->first()->is($studentFee));
            $this->assertTrue($graph['class']->feeStructures->first()->is($structure));
            $this->assertTrue($graph['student']->studentFees->first()->is($studentFee));
            $this->assertTrue($graph['student']->feePayments->first()->is($payment));
            $this->assertTrue($graph['collector']->receivedFeePayments->first()->is($payment));

            $this->assertSame('1000.00', $structure->amount);
            $this->assertSame('2026-05-15', $structure->due_date->toDateString());
            $this->assertSame('1000.00', $studentFee->amount);
            $this->assertSame('50.00', $studentFee->discount_amount);
            $this->assertSame('950.00', $studentFee->payable_amount);
            $this->assertSame('100.00', $studentFee->paid_amount);
            $this->assertSame('850.00', $studentFee->balance_amount);
            $this->assertSame('2026-05-15', $studentFee->due_date->toDateString());
            $this->assertSame('100.00', $payment->amount_paid);
            $this->assertSame('2026-05-10', $payment->payment_date->toDateString());
            $this->assertSame('100.00', $transaction->amount);
            $this->assertSame('2026-05-10 09:30:00', $transaction->processed_at->format('Y-m-d H:i:s'));
            $this->assertSame(['gateway' => 'local_sandbox', 'status' => 'completed'], $transaction->raw_response);

            $this->assertFalse($category->isFillable('school_id'));
            $this->assertFalse($structure->isFillable('school_id'));
            $this->assertFalse($studentFee->isFillable('school_id'));
            $this->assertFalse($payment->isFillable('school_id'));
            $this->assertFalse($transaction->isFillable('school_id'));
            $this->assertTrue(method_exists($category, 'trashed'));
            $this->assertTrue(method_exists($structure, 'trashed'));
            $this->assertTrue(method_exists($studentFee, 'trashed'));
            $this->assertFalse(method_exists($payment, 'trashed'));
            $this->assertFalse(method_exists($transaction, 'trashed'));

            $this->assertSame(['pending', 'partial', 'paid', 'waived', 'cancelled'], [
                StudentFee::STATUS_PENDING,
                StudentFee::STATUS_PARTIAL,
                StudentFee::STATUS_PAID,
                StudentFee::STATUS_WAIVED,
                StudentFee::STATUS_CANCELLED,
            ]);
            $this->assertSame(['cash', 'card', 'upi', 'bank_transfer', 'sandbox_gateway'], [
                FeePayment::MODE_CASH,
                FeePayment::MODE_CARD,
                FeePayment::MODE_UPI,
                FeePayment::MODE_BANK_TRANSFER,
                FeePayment::MODE_SANDBOX_GATEWAY,
            ]);
            $this->assertSame(['completed', 'pending', 'failed', 'reversed'], [
                PaymentTransaction::STATUS_COMPLETED,
                PaymentTransaction::STATUS_PENDING,
                PaymentTransaction::STATUS_FAILED,
                PaymentTransaction::STATUS_REVERSED,
            ]);

            $category->delete();
            $structure->delete();
            $studentFee->delete();
            $graph['student']->delete();
            $graph['class']->delete();
            $graph['collector']->delete();

            $payment = $payment->fresh();
            $transaction = $transaction->fresh();

            $this->assertTrue($payment->studentFee->is($studentFee));
            $this->assertTrue($payment->student->is($graph['student']));
            $this->assertTrue($payment->receivedBy->is($graph['collector']));
            $this->assertTrue($payment->studentFee->feeStructure->is($structure));
            $this->assertTrue($payment->studentFee->feeStructure->feeCategory->is($category));
            $this->assertTrue($payment->studentFee->feeStructure->schoolClass->is($graph['class']));
            $this->assertTrue($transaction->feePayment->is($payment));
        });
    }

    public function test_unique_foreign_key_and_restrict_delete_constraints_preserve_fee_integrity(): void
    {
        $school = $this->school('One');
        $graph = $this->createFeeGraph($school, 'ONE');

        app(TenantContext::class)->runAsTenant($school->id, function () use ($graph): void {
            $this->assertQueryFails(fn () => FeeCategory::create($this->feeCategoryPayload()));
            $this->assertQueryFails(fn () => FeeStructure::create($this->feeStructurePayload($graph)));
            $this->assertQueryFails(fn () => StudentFee::create($this->studentFeePayload($graph)));
            $this->assertQueryFails(fn () => FeePayment::create($this->feePaymentPayload($graph)));
            $this->assertQueryFails(fn () => PaymentTransaction::create($this->paymentTransactionPayload($graph)));

            foreach (['fee_category_id', 'academic_year_id', 'class_id'] as $foreignKey) {
                $payload = $this->feeStructurePayload($graph);
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => FeeStructure::create($payload));
            }

            foreach (['student_id', 'fee_structure_id', 'academic_year_id'] as $foreignKey) {
                $payload = $this->studentFeePayload($graph);
                $payload[$foreignKey] = 999999;

                $this->assertQueryFails(fn () => StudentFee::create($payload));
            }

            foreach (['student_fee_id', 'student_id', 'received_by'] as $foreignKey) {
                $payload = $this->feePaymentPayload($graph);
                $payload[$foreignKey] = 999999;
                $payload['receipt_no'] = 'FEE-ONE-'.$foreignKey;

                $this->assertQueryFails(fn () => FeePayment::create($payload));
            }

            $payload = $this->paymentTransactionPayload($graph);
            $payload['fee_payment_id'] = 999999;
            $payload['transaction_no'] = 'TXN-ONE-invalid';
            $this->assertQueryFails(fn () => PaymentTransaction::create($payload));

            $this->assertQueryFails(fn () => DB::table('fee_categories')->insert([
                'school_id' => 999999,
                'name' => 'Forged Fee',
                'status' => FeeCategory::STATUS_ACTIVE,
            ]));

            $this->assertQueryFails(fn () => FeeCategory::withTrashed()->findOrFail($graph['category']->id)->forceDelete());
            $this->assertQueryFails(fn () => FeeStructure::withTrashed()->findOrFail($graph['structure']->id)->forceDelete());
            $this->assertQueryFails(fn () => StudentFee::withTrashed()->findOrFail($graph['studentFee']->id)->forceDelete());
            $this->assertQueryFails(fn () => Student::withTrashed()->findOrFail($graph['student']->id)->forceDelete());
            $this->assertQueryFails(fn () => SchoolClass::withTrashed()->findOrFail($graph['class']->id)->forceDelete());
            $this->assertQueryFails(fn () => User::withTrashed()->findOrFail($graph['collector']->id)->forceDelete());
            $this->assertQueryFails(fn () => DB::table('fee_payments')->where('id', $graph['payment']->id)->delete());
        });
    }

    private function assertTenantCreateDenied(callable $create): void
    {
        try {
            $create();
            $this->fail('A strict tenant-owned Fee record was created without Tenant context.');
        } catch (TenantContextException $exception) {
            $this->assertStringContainsString('Tenant context is required', $exception->getMessage());
        }
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('A database integrity constraint was not enforced.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @return array{
     *     year: AcademicYear,
     *     class: SchoolClass,
     *     student: Student,
     *     enrollment: StudentEnrollment,
     *     collector: User,
     *     category: FeeCategory,
     *     structure: FeeStructure,
     *     studentFee: StudentFee,
     *     payment: FeePayment,
     *     transaction: PaymentTransaction
     * }
     */
    private function createFeeGraph(School $school, string $suffix, ?int $forgedSchoolId = null): array
    {
        return app(TenantContext::class)->runAsTenant($school->id, function () use ($school, $suffix, $forgedSchoolId): array {
            $forgedSchoolId ??= $school->id + 1000;
            $year = AcademicYear::create([
                'name' => '2026-2027 '.$suffix,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
            ]);
            $schoolClass = SchoolClass::create([
                'name' => 'Class '.$suffix,
                'code' => 'CLS-'.$suffix,
            ]);
            $student = Student::create([
                'admission_no' => 'FEE-ADM-'.$suffix,
                'first_name' => 'Student',
                'last_name' => $suffix,
                'gender' => Student::GENDER_PREFER_NOT_TO_SAY,
                'date_of_birth' => '2015-05-10',
                'guardian_name' => 'Guardian '.$suffix,
                'guardian_phone' => '9876500000',
                'admission_date' => '2026-04-02',
            ]);
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'class_id' => $schoolClass->id,
                'section_id' => $this->sectionFor($schoolClass, $suffix)->id,
                'roll_no' => 'FEE-ROLL-'.$suffix,
                'enrollment_date' => '2026-04-02',
            ]);
            $collector = User::factory()->create([
                'role_id' => Role::query()->where('code', Role::ACCOUNTANT)->value('id'),
                'school_id' => $school->id,
                'email' => strtolower('fee-'.$suffix).'@example.com',
            ]);
            $category = FeeCategory::create([
                ...$this->feeCategoryPayload(),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $structure = FeeStructure::create([
                ...$this->feeStructurePayload([
                    'category' => $category,
                    'year' => $year,
                    'class' => $schoolClass,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $studentFee = StudentFee::create([
                ...$this->studentFeePayload([
                    'student' => $student,
                    'structure' => $structure,
                    'year' => $year,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $payment = FeePayment::create([
                ...$this->feePaymentPayload([
                    'studentFee' => $studentFee,
                    'student' => $student,
                    'collector' => $collector,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();
            $transaction = PaymentTransaction::create([
                ...$this->paymentTransactionPayload([
                    'payment' => $payment,
                ]),
                'school_id' => $forgedSchoolId,
            ])->refresh();

            return [
                'year' => $year,
                'class' => $schoolClass,
                'student' => $student,
                'enrollment' => $enrollment,
                'collector' => $collector,
                'category' => $category,
                'structure' => $structure,
                'studentFee' => $studentFee,
                'payment' => $payment,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * @return array{name: string, description: string, status: string}
     */
    private function feeCategoryPayload(): array
    {
        return [
            'name' => 'Tuition Fee',
            'description' => 'Standard tuition fee.',
            'status' => FeeCategory::STATUS_ACTIVE,
        ];
    }

    /**
     * @param  array<string, AcademicYear|FeeCategory|SchoolClass>  $graph
     * @return array<string, int|string>
     */
    private function feeStructurePayload(array $graph): array
    {
        return [
            'fee_category_id' => $graph['category']->id,
            'academic_year_id' => $graph['year']->id,
            'class_id' => $graph['class']->id,
            'amount' => '1000.00',
            'due_date' => '2026-05-15',
            'frequency' => FeeStructure::FREQUENCY_ONE_TIME,
            'status' => FeeStructure::STATUS_ACTIVE,
        ];
    }

    /**
     * @param  array<string, AcademicYear|FeeStructure|Student>  $graph
     * @return array<string, int|string>
     */
    private function studentFeePayload(array $graph): array
    {
        return [
            'student_id' => $graph['student']->id,
            'fee_structure_id' => $graph['structure']->id,
            'academic_year_id' => $graph['year']->id,
            'amount' => '1000.00',
            'discount_amount' => '50.00',
            'payable_amount' => '950.00',
            'paid_amount' => '100.00',
            'balance_amount' => '850.00',
            'due_date' => '2026-05-15',
            'status' => StudentFee::STATUS_PARTIAL,
        ];
    }

    /**
     * @param  array<string, Student|StudentFee|User>  $graph
     * @return array<string, int|string>
     */
    private function feePaymentPayload(array $graph): array
    {
        return [
            'student_fee_id' => $graph['studentFee']->id,
            'student_id' => $graph['student']->id,
            'receipt_no' => 'FEE-ONE-0001',
            'amount_paid' => '100.00',
            'payment_date' => '2026-05-10',
            'payment_mode' => FeePayment::MODE_SANDBOX_GATEWAY,
            'status' => FeePayment::STATUS_COMPLETED,
            'received_by' => $graph['collector']->id,
            'remarks' => 'Demo sandbox collection.',
        ];
    }

    /**
     * @param  array<string, FeePayment>  $graph
     * @return array<string, mixed>
     */
    private function paymentTransactionPayload(array $graph): array
    {
        return [
            'fee_payment_id' => $graph['payment']->id,
            'transaction_no' => 'TXN-ONE-0001',
            'gateway_reference' => 'SANDBOX-ONE-0001',
            'amount' => '100.00',
            'payment_mode' => PaymentTransaction::MODE_SANDBOX_GATEWAY,
            'status' => PaymentTransaction::STATUS_COMPLETED,
            'processed_at' => '2026-05-10 09:30:00',
            'raw_response' => ['gateway' => 'local_sandbox', 'status' => 'completed'],
        ];
    }

    private function sectionFor(SchoolClass $schoolClass, string $suffix): Section
    {
        return Section::create([
            'class_id' => $schoolClass->id,
            'name' => 'Section '.$suffix,
        ]);
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'School '.$suffix,
            'code' => 'FEE-'.$suffix,
            'email' => strtolower('fee-'.$suffix).'@school.example.com',
            'status' => School::STATUS_ACTIVE,
        ]);
    }
}
