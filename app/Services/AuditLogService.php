<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Concerns\AuthorizesLogReview;
use App\Support\PrivacySafeLogValues;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService
{
    use AuthorizesLogReview;

    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     event?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('viewAny', AuditLog::class));

        return AuditLog::query()
            ->with([
                'user:id,name,email',
                'school:id,name,code',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('event', 'like', $search)
                        ->orWhere('auditable_type', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when(filled($filters['event'] ?? null), fn ($query) => $query->where('event', $filters['event']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function showFor(AuditLog $auditLog, User $actor): AuditLog
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('view', $auditLog));

        return $auditLog->load([
            'user:id,name,email',
            'school:id,name,code',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizedOldValues(AuditLog $auditLog): array
    {
        return PrivacySafeLogValues::sanitize($auditLog->old_values);
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizedNewValues(AuditLog $auditLog): array
    {
        return PrivacySafeLogValues::sanitize($auditLog->new_values);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     event?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return iterable<int, list<string|null>>
     */
    public function exportRowsFor(User $actor, array $filters): iterable
    {
        $this->authorizeLogReviewActor($actor);
        $this->authorizeLogReview($actor->can('viewAny', AuditLog::class));

        $includeSchool = $this->tenantContext->isPlatform();

        $query = AuditLog::query()
            ->with([
                'user:id,name,email',
                'school:id,name,code',
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('event', 'like', $search)
                        ->orWhere('auditable_type', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when(filled($filters['event'] ?? null), fn ($query) => $query->where('event', $filters['event']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        foreach ($query->cursor() as $auditLog) {
            $row = [
                $auditLog->created_at?->format('Y-m-d H:i:s'),
                $auditLog->event,
                $auditLog->auditable_type,
                $auditLog->auditable_id !== null ? (string) $auditLog->auditable_id : null,
                $auditLog->user?->name,
                $auditLog->ip_address,
            ];

            if ($includeSchool) {
                array_splice($row, 4, 0, [$auditLog->school?->name]);
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
            'Event',
            'Auditable Type',
            'Auditable ID',
            'User',
            'IP Address',
        ];

        if ($this->tenantContext->isPlatform()) {
            array_splice($headers, 4, 0, ['School']);
        }

        return $headers;
    }
}
