<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SandboxTransactionService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, PaymentTransaction>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeFeeOperatorContext($actor);
        $this->authorize($actor->can('viewAny', PaymentTransaction::class));

        return PaymentTransaction::query()
            ->where('payment_mode', PaymentTransaction::MODE_SANDBOX_GATEWAY)
            ->with([
                'feePayment.student:id,admission_no,first_name,last_name',
                'feePayment.studentFee.feeStructure.feeCategory',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('transaction_no', 'like', $search)
                        ->orWhere('gateway_reference', 'like', $search)
                        ->orWhereHas('feePayment', fn ($query) => $query
                            ->where('receipt_no', 'like', $search)
                            ->orWhereHas('student', fn ($query) => $query
                                ->where('admission_no', 'like', $search)
                                ->orWhere('first_name', 'like', $search)
                                ->orWhere('last_name', 'like', $search)));
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('processed_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('processed_at', '<=', $filters['date_to']))
            ->orderByDesc('processed_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function showFor(PaymentTransaction $paymentTransaction, User $actor): PaymentTransaction
    {
        $this->authorizeFeeOperatorContext($actor);

        if ($paymentTransaction->payment_mode !== PaymentTransaction::MODE_SANDBOX_GATEWAY) {
            throw new NotFoundHttpException;
        }

        $this->authorize($actor->can('view', $paymentTransaction));

        return $paymentTransaction->load([
            'feePayment.student:id,admission_no,first_name,last_name',
            'feePayment.studentFee.feeStructure.feeCategory',
            'feePayment.studentFee.feeStructure.schoolClass',
            'feePayment.receivedBy:id,name',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizedPayload(PaymentTransaction $paymentTransaction): array
    {
        return collect($paymentTransaction->raw_response ?? [])
            ->only(['gateway', 'reference', 'amount', 'status', 'processed_at'])
            ->all();
    }

    private function authorizeFeeOperatorContext(User $actor): void
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
}
