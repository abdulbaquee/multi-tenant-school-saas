<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\StudentFee;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FeeReportService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReportService $reports,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function reportFor(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->authorizeCategory($actor);

        $filters = $this->normalizeFilters($filters);

        if ($actor->isSuperAdmin()) {
            return $this->platformReport($actor, $filters);
        }

        return $this->schoolReport($actor, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<list<string|int>>
     */
    public function exportRowsFor(User $actor, array $filters): array
    {
        $this->authorizeActorContext($actor);
        $this->authorizeCategory($actor);
        $filters = $this->normalizeFilters($filters);

        if ($actor->isSuperAdmin()) {
            $schools = School::query()
                ->withCount([
                    'studentFees as fee_assignments_count' => function (Builder $builder) use ($filters): void {
                        $this->applyFeeFilters($builder, $filters);
                    },
                    'studentFees as outstanding_assignments_count' => function (Builder $builder) use ($filters): void {
                        $this->applyFeeFilters($builder, $filters);
                        $builder
                            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                            ->where('balance_amount', '>', 0);
                    },
                    'studentFees as paid_assignments_count' => function (Builder $builder) use ($filters): void {
                        $this->applyFeeFilters($builder, $filters);
                        $builder->where('status', StudentFee::STATUS_PAID);
                    },
                ])
                ->withSum([
                    'studentFees as outstanding_balance_total' => function (Builder $builder) use ($filters): void {
                        $this->applyFeeFilters($builder, $filters);
                        $builder
                            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                            ->where('balance_amount', '>', 0);
                    },
                ], 'balance_amount')
                ->when(filled($filters['search'] ?? null), function (Builder $builder) use ($filters): void {
                    $search = '%'.$filters['search'].'%';
                    $builder->where(fn (Builder $nested) => $nested
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search));
                })
                ->orderBy('name')
                ->get();

            return $schools->map(fn (School $school): array => [
                $school->code,
                $school->name,
                (string) ($school->fee_assignments_count ?? 0),
                (string) ($school->outstanding_assignments_count ?? 0),
                (string) ($school->paid_assignments_count ?? 0),
                (string) number_format((float) ($school->outstanding_balance_total ?? 0), 2, '.', ''),
            ])->all();
        }

        $baseQuery = $this->scopedFeeQuery($actor);
        $this->applyFeeFilters($baseQuery, $filters);

        $studentFees = (clone $baseQuery)
            ->with([
                'student:id,admission_no,first_name,last_name',
                'feeStructure.feeCategory',
                'academicYear',
            ])
            ->orderByDesc('balance_amount')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return $studentFees->map(fn (StudentFee $studentFee): array => [
            $studentFee->student->admission_no,
            trim($studentFee->student->first_name.' '.$studentFee->student->last_name),
            $studentFee->feeStructure->feeCategory->name,
            number_format((float) $studentFee->payable_amount, 2, '.', ''),
            number_format((float) $studentFee->paid_amount, 2, '.', ''),
            number_format((float) $studentFee->balance_amount, 2, '.', ''),
            ucfirst((string) $studentFee->status),
            $studentFee->due_date?->format('Y-m-d') ?? '—',
        ])->all();
    }

    /**
     * @return list<string>
     */
    public function exportHeadersFor(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? ['School Code', 'School Name', 'Assignments', 'Outstanding', 'Paid', 'Outstanding Balance']
            : ['Admission No', 'Student Name', 'Category', 'Payable', 'Paid', 'Balance', 'Status', 'Due Date'];
    }

    public function logExport(User $actor, int $rowCount): void
    {
        $this->securityLogs->activity(
            $actor,
            'reporting',
            'exported',
            null,
            'Fee report exported with '.$rowCount.' row(s).',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function platformReport(User $actor, array $filters): array
    {
        $query = School::query()
            ->withCount([
                'studentFees as fee_assignments_count' => function (Builder $builder) use ($filters): void {
                    $this->applyFeeFilters($builder, $filters);
                },
                'studentFees as outstanding_assignments_count' => function (Builder $builder) use ($filters): void {
                    $this->applyFeeFilters($builder, $filters);
                    $builder
                        ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                        ->where('balance_amount', '>', 0);
                },
                'studentFees as paid_assignments_count' => function (Builder $builder) use ($filters): void {
                    $this->applyFeeFilters($builder, $filters);
                    $builder->where('status', StudentFee::STATUS_PAID);
                },
            ])
            ->withSum([
                'studentFees as outstanding_balance_total' => function (Builder $builder) use ($filters): void {
                    $this->applyFeeFilters($builder, $filters);
                    $builder
                        ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                        ->where('balance_amount', '>', 0);
                },
            ], 'balance_amount')
            ->when(filled($filters['search'] ?? null), function (Builder $builder) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $builder->where(fn (Builder $nested) => $nested
                    ->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search));
            })
            ->orderBy('name');

        $schools = $query->paginate(15)->withQueryString();
        $totals = StudentFee::query();
        $this->applyFeeFilters($totals, $filters);

        return [
            'mode' => 'platform',
            'title' => 'Fee Reports',
            'description' => 'Platform-wide fee assignment and outstanding balance summaries by school.',
            'summary' => [
                [
                    'label' => 'Schools listed',
                    'value' => $schools->total(),
                    'icon' => 'bi-buildings',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Fee assignments',
                    'value' => (clone $totals)->count(),
                    'icon' => 'bi-cash-coin',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Outstanding balance',
                    'value' => number_format((float) ((clone $totals)
                        ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                        ->where('balance_amount', '>', 0)
                        ->sum(DB::raw('balance_amount')) ?: 0), 2, '.', ''),
                    'icon' => 'bi-exclamation-circle',
                    'tone' => 'secondary',
                ],
            ],
            'schools' => $schools,
            'canExport' => Gate::forUser($actor)->allows('reports.fees.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function schoolReport(User $actor, array $filters): array
    {
        $baseQuery = $this->scopedFeeQuery($actor);
        $this->applyFeeFilters($baseQuery, $filters);

        $summaryQuery = clone $baseQuery;

        $studentFees = (clone $baseQuery)
            ->with([
                'student:id,admission_no,first_name,last_name',
                'feeStructure.feeCategory',
                'academicYear',
            ])
            ->orderByDesc('balance_amount')
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return [
            'mode' => 'school',
            'title' => 'Fee Reports',
            'description' => 'Fee assignment, collection, and outstanding balance summaries for your authorized scope.',
            'summary' => [
                [
                    'label' => 'Matching assignments',
                    'value' => $studentFees->total(),
                    'icon' => 'bi-cash-coin',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Outstanding',
                    'value' => (clone $summaryQuery)
                        ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
                        ->where('balance_amount', '>', 0)
                        ->count(),
                    'icon' => 'bi-exclamation-circle',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Paid assignments',
                    'value' => (clone $summaryQuery)->where('status', StudentFee::STATUS_PAID)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'success',
                ],
            ],
            'studentFees' => $studentFees,
            'canExport' => Gate::forUser($actor)->allows('reports.fees.export'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{search: ?string, date_from: ?string, date_to: ?string, state: ?string}
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = $this->reports->normalizeFilters($filters);
        $state = filled($filters['state'] ?? null) ? (string) $filters['state'] : null;

        if ($state !== null && ! in_array($state, [
            StudentFee::STATUS_PENDING,
            StudentFee::STATUS_PARTIAL,
            StudentFee::STATUS_PAID,
            StudentFee::STATUS_WAIVED,
        ], true)) {
            $state = null;
        }

        return [
            ...$normalized,
            'state' => $state,
        ];
    }

    /**
     * @param  Builder<StudentFee>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFeeFilters(Builder $query, array $filters): void
    {
        $query
            ->when(filled($filters['date_from'] ?? null), fn (Builder $builder) => $builder->whereDate('due_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $builder) => $builder->whereDate('due_date', '<=', $filters['date_to']))
            ->when(filled($filters['state'] ?? null), fn (Builder $builder) => $builder->where('status', $filters['state']));

        if (! filled($filters['search'] ?? null)) {
            return;
        }

        $search = '%'.$filters['search'].'%';
        $query->where(function (Builder $nested) use ($search): void {
            $nested
                ->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                    ->where('admission_no', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search))
                ->orWhereHas('feeStructure.feeCategory', fn (Builder $categoryQuery) => $categoryQuery
                    ->where('name', 'like', $search));
        });
    }

    /**
     * @return Builder<StudentFee>
     */
    private function scopedFeeQuery(User $actor): Builder
    {
        return StudentFee::query()->withTrashed();
    }

    private function authorizeCategory(User $actor): void
    {
        if (! Gate::forUser($actor)->allows('reports.fees.view')) {
            throw new AuthorizationException;
        }
    }

    private function authorizeActorContext(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $this->tenantContext->isTenant()
                && filled($actor->school_id)
                && (int) $this->tenantContext->schoolId() === (int) $actor->school_id
                && ($actor->hasRoleCode(Role::SCHOOL_ADMIN) || $actor->hasRoleCode(Role::ACCOUNTANT));

        if (! $matchesContext) {
            throw new AuthorizationException;
        }
    }
}
