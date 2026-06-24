<?php

namespace App\Services;

use App\Models\Role;
use App\Models\StudentFee;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeeOutstandingBalanceService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return array{
     *     studentFees: LengthAwarePaginator<int, StudentFee>,
     *     summary: array{
     *         assignment_count: int,
     *         pending_count: int,
     *         partial_count: int,
     *         total_outstanding: string
     *     }
     * }
     */
    public function listFor(User $actor, array $filters): array
    {
        $this->authorizeFeeOperatorContext($actor);
        $this->authorize($actor->can('viewOutstandingAny', StudentFee::class));

        $baseQuery = StudentFee::query()
            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
            ->where('balance_amount', '>', 0);

        $summaryQuery = clone $baseQuery;

        $studentFees = (clone $baseQuery)
            ->with([
                'student:id,admission_no,first_name,last_name',
                'feeStructure.feeCategory',
                'feeStructure.schoolClass',
                'academicYear',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($query) => $query
                        ->where('admission_no', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search));
                });
            })
            ->when(($filters['state'] ?? null) === StudentFee::STATUS_PENDING, fn ($query) => $query->where('status', StudentFee::STATUS_PENDING))
            ->when(($filters['state'] ?? null) === StudentFee::STATUS_PARTIAL, fn ($query) => $query->where('status', StudentFee::STATUS_PARTIAL))
            ->orderByDesc('balance_amount')
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return [
            'studentFees' => $studentFees,
            'summary' => [
                'assignment_count' => (int) (clone $summaryQuery)->count(),
                'pending_count' => (int) (clone $summaryQuery)->where('status', StudentFee::STATUS_PENDING)->count(),
                'partial_count' => (int) (clone $summaryQuery)->where('status', StudentFee::STATUS_PARTIAL)->count(),
                'total_outstanding' => number_format((float) ((clone $summaryQuery)->sum(DB::raw('balance_amount')) ?: 0), 2, '.', ''),
            ],
        ];
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
