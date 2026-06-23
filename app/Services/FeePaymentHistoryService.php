<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeePaymentHistoryService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     payment_mode?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, FeePayment>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeFeeOperatorContext($actor);
        $this->authorize($actor->can('viewAny', FeePayment::class));

        return FeePayment::query()
            ->with([
                'student:id,admission_no,first_name,last_name',
                'studentFee.feeStructure.feeCategory',
                'receivedBy:id,name',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('receipt_no', 'like', $search)
                        ->orWhereHas('student', fn ($query) => $query
                            ->where('admission_no', 'like', $search)
                            ->orWhere('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search));
                });
            })
            ->when(filled($filters['payment_mode'] ?? null), fn ($query) => $query->where('payment_mode', $filters['payment_mode']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('payment_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('payment_date', '<=', $filters['date_to']))
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
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
