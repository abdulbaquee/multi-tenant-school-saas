<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Concerns\AuthorizesLogReview;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    use AuthorizesLogReview;

    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     module?: string|null,
     *     action?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('viewAny', ActivityLog::class));

        return ActivityLog::query()
            ->with([
                'user:id,name,email',
                'school:id,name,code',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('module', 'like', $search)
                        ->orWhere('action', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when(filled($filters['module'] ?? null), fn ($query) => $query->where('module', $filters['module']))
            ->when(filled($filters['action'] ?? null), fn ($query) => $query->where('action', $filters['action']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function showFor(ActivityLog $activityLog, User $actor): ActivityLog
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('view', $activityLog));

        return $activityLog->load([
            'user:id,name,email',
            'school:id,name,code',
        ]);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     module?: string|null,
     *     action?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return iterable<int, list<string|null>>
     */
    public function exportRowsFor(User $actor, array $filters): iterable
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('viewAny', ActivityLog::class));

        $includeSchool = $this->tenantContext->isPlatform();

        $query = ActivityLog::query()
            ->with([
                'user:id,name,email',
                'school:id,name,code',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('module', 'like', $search)
                        ->orWhere('action', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when(filled($filters['module'] ?? null), fn ($query) => $query->where('module', $filters['module']))
            ->when(filled($filters['action'] ?? null), fn ($query) => $query->where('action', $filters['action']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        foreach ($query->cursor() as $activityLog) {
            $row = [
                $activityLog->created_at?->format('Y-m-d H:i:s'),
                $activityLog->module,
                $activityLog->action,
                $activityLog->user?->name,
                $activityLog->description,
                $activityLog->subject_type,
                $activityLog->subject_id !== null ? (string) $activityLog->subject_id : null,
                $activityLog->ip_address,
            ];

            if ($includeSchool) {
                array_splice($row, 4, 0, [$activityLog->school?->name]);
            }

            yield $row;
        }
    }

    /**
     * @return list<string>
     */
    public function exportHeaders(): array
    {
        $headers = [
            'Recorded At',
            'Module',
            'Action',
            'User',
            'Description',
            'Subject Type',
            'Subject ID',
            'IP Address',
        ];

        if ($this->tenantContext->isPlatform()) {
            array_splice($headers, 4, 0, ['School']);
        }

        return $headers;
    }
}
