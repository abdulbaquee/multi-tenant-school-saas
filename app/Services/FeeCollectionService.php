<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\StudentFee;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeCollectionService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null}  $filters
     * @return LengthAwarePaginator<int, StudentFee>
     */
    public function collectibleListFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeCollectorContext($actor);
        $this->authorize($actor->can('viewAny', StudentFee::class));

        return StudentFee::query()
            ->with([
                'student',
                'feeStructure.feeCategory',
                'feeStructure.schoolClass',
                'academicYear',
            ])
            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
            ->where('balance_amount', '>', 0)
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($query) => $query
                        ->where('admission_no', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function collectionFormFor(StudentFee $studentFee, User $actor): StudentFee
    {
        $this->authorizeCollectorContext($actor);
        $this->authorize($actor->can('collect', $studentFee));

        return $studentFee->load([
            'student',
            'feeStructure.feeCategory',
            'feeStructure.schoolClass',
            'academicYear',
        ]);
    }

    public function issueCollectionToken(StudentFee $studentFee, User $actor): string
    {
        $this->authorizeCollectorContext($actor);
        $this->authorize($actor->can('collect', $studentFee));

        $token = (string) Str::uuid();
        session()->put($this->collectionTokenKey($studentFee), $token);

        return $token;
    }

    /**
     * @param  array{
     *     amount_paid: numeric-string|float|int,
     *     payment_date: string,
     *     payment_mode: string,
     *     remarks?: string|null,
     *     collection_token: string
     * }  $data
     */
    public function collect(StudentFee $studentFee, array $data, User $actor): FeePayment
    {
        $this->authorizeCollectorContext($actor);
        $this->authorize($actor->can('collect', $studentFee));

        return DB::transaction(function () use ($studentFee, $data, $actor): FeePayment {
            $this->lockTenantSchool();
            $this->assertCollectionToken($studentFee, $data['collection_token']);

            $lockedFee = StudentFee::query()
                ->lockForUpdate()
                ->with(['student'])
                ->whereKey($studentFee->getKey())
                ->firstOrFail();

            $this->assertCollectible($lockedFee);

            $amountPaid = $this->formatMoney($data['amount_paid']);
            $balance = $this->formatMoney($lockedFee->balance_amount);

            if (bccomp($amountPaid, '0.00', 2) !== 1) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (bccomp($amountPaid, $balance, 2) === 1) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment amount cannot exceed the remaining balance.',
                ]);
            }

            $paymentDate = $this->validatePaymentDate((string) $data['payment_date']);
            $paymentMode = $this->validatePaymentMode((string) $data['payment_mode']);
            $remarks = $this->sanitizeRemarks($data['remarks'] ?? null);

            $receiptNo = $this->nextReceiptNumber();
            $transactionNo = $this->nextTransactionNumber();

            $newPaid = bcadd($this->formatMoney($lockedFee->paid_amount), $amountPaid, 2);
            $newBalance = bcsub($balance, $amountPaid, 2);
            $newStatus = bccomp($newBalance, '0.00', 2) === 0
                ? StudentFee::STATUS_PAID
                : StudentFee::STATUS_PARTIAL;

            $oldFeeValues = $this->studentFeeAuditValues($lockedFee);

            $lockedFee->forceFill([
                'paid_amount' => $newPaid,
                'balance_amount' => $newBalance,
                'status' => $newStatus,
            ])->save();

            $feePayment = FeePayment::create([
                'student_fee_id' => $lockedFee->id,
                'student_id' => $lockedFee->student_id,
                'receipt_no' => $receiptNo,
                'amount_paid' => $amountPaid,
                'payment_date' => $paymentDate->toDateString(),
                'payment_mode' => $paymentMode,
                'status' => FeePayment::STATUS_COMPLETED,
                'received_by' => $actor->id,
                'remarks' => $remarks,
            ]);

            PaymentTransaction::create([
                'fee_payment_id' => $feePayment->id,
                'transaction_no' => $transactionNo,
                'gateway_reference' => $paymentMode === FeePayment::MODE_SANDBOX_GATEWAY
                    ? 'SANDBOX-'.$transactionNo
                    : null,
                'amount' => $amountPaid,
                'payment_mode' => $paymentMode,
                'status' => PaymentTransaction::STATUS_COMPLETED,
                'processed_at' => now(),
                'raw_response' => $this->sandboxResponse($paymentMode, $transactionNo, $amountPaid),
            ]);

            $this->logStudentFeeMutation($lockedFee, $actor, 'collected', $oldFeeValues);
            $this->logPaymentMutation($feePayment, $actor, 'collected', [], $this->paymentAuditValues($feePayment));

            return $feePayment->load([
                'student',
                'studentFee.feeStructure.feeCategory',
                'studentFee.feeStructure.schoolClass',
                'receivedBy',
                'paymentTransactions',
            ]);
        });
    }

    public function receiptFor(FeePayment $feePayment, User $actor): FeePayment
    {
        $this->authorizeCollectorContext($actor);
        $this->authorize($actor->can('view', $feePayment));

        return $feePayment->load([
            'student',
            'studentFee.feeStructure.feeCategory',
            'studentFee.feeStructure.schoolClass',
            'studentFee.academicYear',
            'receivedBy',
            'paymentTransactions',
        ]);
    }

    private function assertCollectionToken(StudentFee $studentFee, string $token): void
    {
        $expected = session()->pull($this->collectionTokenKey($studentFee));

        if (! is_string($expected) || ! hash_equals($expected, $token)) {
            throw ValidationException::withMessages([
                'collection_token' => 'This collection request is no longer valid. Reload the form and try again.',
            ]);
        }
    }

    private function collectionTokenKey(StudentFee $studentFee): string
    {
        return 'fee_collection_token_'.$studentFee->getKey();
    }

    private function assertCollectible(StudentFee $studentFee): void
    {
        if (! in_array($studentFee->status, [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL], true)) {
            throw ValidationException::withMessages([
                'student_fee_id' => 'This Student Fee is not open for collection.',
            ]);
        }

        if (bccomp($this->formatMoney($studentFee->balance_amount), '0.00', 2) !== 1) {
            throw ValidationException::withMessages([
                'student_fee_id' => 'This Student Fee has no remaining balance.',
            ]);
        }
    }

    private function validatePaymentDate(string $paymentDate): CarbonImmutable
    {
        $timezone = $this->schoolTimezone();

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $paymentDate, $timezone)?->startOfDay();
        } catch (\Throwable) {
            $date = null;
        }

        if (! $date instanceof CarbonImmutable) {
            throw ValidationException::withMessages([
                'payment_date' => 'Enter a valid payment date.',
            ]);
        }

        if ($date->gt(CarbonImmutable::now($timezone)->startOfDay())) {
            throw ValidationException::withMessages([
                'payment_date' => 'Payment date cannot be in the future.',
            ]);
        }

        return $date;
    }

    private function validatePaymentMode(string $paymentMode): string
    {
        $allowed = [
            FeePayment::MODE_CASH,
            FeePayment::MODE_CARD,
            FeePayment::MODE_UPI,
            FeePayment::MODE_BANK_TRANSFER,
            FeePayment::MODE_SANDBOX_GATEWAY,
        ];

        if (! in_array($paymentMode, $allowed, true)) {
            throw ValidationException::withMessages([
                'payment_mode' => 'Select a valid payment mode.',
            ]);
        }

        return $paymentMode;
    }

    private function sanitizeRemarks(?string $remarks): ?string
    {
        if (! filled($remarks)) {
            return null;
        }

        $trimmed = trim($remarks);

        if ($trimmed === '') {
            return null;
        }

        if (strlen($trimmed) > 500) {
            throw ValidationException::withMessages([
                'remarks' => 'Remarks cannot exceed 500 characters.',
            ]);
        }

        return $trimmed;
    }

    private function nextReceiptNumber(): string
    {
        $count = FeePayment::query()->lockForUpdate()->count() + 1;

        return sprintf('RCP-%06d', $count);
    }

    private function nextTransactionNumber(): string
    {
        $count = PaymentTransaction::query()->lockForUpdate()->count() + 1;

        return sprintf('TXN-%06d', $count);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sandboxResponse(string $paymentMode, string $transactionNo, string $amountPaid): ?array
    {
        if ($paymentMode !== FeePayment::MODE_SANDBOX_GATEWAY) {
            return null;
        }

        return [
            'gateway' => 'local_sandbox',
            'reference' => 'SANDBOX-'.$transactionNo,
            'amount' => $amountPaid,
            'status' => PaymentTransaction::STATUS_COMPLETED,
            'processed_at' => now()->toIso8601String(),
        ];
    }

    private function schoolTimezone(): string
    {
        return (string) (SchoolSetting::query()->value('timezone') ?: config('app.timezone', 'UTC'));
    }

    private function formatMoney(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function authorizeCollectorContext(User $actor): void
    {
        $valid = filled($actor->school_id)
            && ($actor->hasRoleCode(Role::SCHOOL_ADMIN) || $actor->hasRoleCode(Role::ACCOUNTANT))
            && $actor->canEstablishTenantContext()
            && $this->tenantContext->isTenant()
            && $this->tenantContext->tenantId() === (int) $actor->school_id;

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function studentFeeAuditValues(StudentFee $studentFee): array
    {
        return [
            'paid_amount' => $studentFee->paid_amount,
            'balance_amount' => $studentFee->balance_amount,
            'status' => $studentFee->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAuditValues(FeePayment $feePayment): array
    {
        return [
            'student_fee_id' => $feePayment->student_fee_id,
            'student_id' => $feePayment->student_id,
            'receipt_no' => $feePayment->receipt_no,
            'amount_paid' => $feePayment->amount_paid,
            'payment_date' => $feePayment->payment_date?->toDateString(),
            'payment_mode' => $feePayment->payment_mode,
            'status' => $feePayment->status,
            'received_by' => $feePayment->received_by,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logStudentFeeMutation(
        StudentFee $studentFee,
        User $actor,
        string $action,
        array $oldValues,
    ): void {
        $newValues = $this->studentFeeAuditValues($studentFee);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->securityLogs->activity($actor, 'fee_management', $action, $studentFee, 'Student Fee '.$action.'.');
        $this->securityLogs->audit(
            $actor,
            $studentFee,
            $action,
            array_intersect_key($oldValues, array_flip($changedKeys)),
            array_intersect_key($newValues, array_flip($changedKeys)),
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logPaymentMutation(
        FeePayment $feePayment,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'fee_management', $action, $feePayment, 'Fee Payment '.$action.'.');
        $this->securityLogs->audit($actor, $feePayment, $action, $oldValues, $newValues);
    }
}
