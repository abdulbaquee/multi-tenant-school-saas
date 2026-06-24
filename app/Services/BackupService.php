<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use App\Support\PlatformDatabaseExporter;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
        private readonly PlatformDatabaseExporter $exporter,
    ) {}

    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, BackupLog>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizePlatformSuperAdmin($actor);
        $this->authorize($actor->can('viewAny', BackupLog::class));

        return BackupLog::query()
            ->with('generatedBy:id,name,email')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('file_path', 'like', $search)
                        ->orWhere('error_message', 'like', $search)
                        ->orWhereHas('generatedBy', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('started_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('started_at', '<=', $filters['date_to']))
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function showFor(BackupLog $backupLog, User $actor): BackupLog
    {
        $this->authorizePlatformSuperAdmin($actor);
        $this->authorize($actor->can('view', $backupLog));

        return $backupLog->load('generatedBy:id,name,email');
    }

    public function createManualPlatformBackup(User $actor): BackupLog
    {
        $this->authorizePlatformSuperAdmin($actor);
        $this->authorize($actor->can('create', BackupLog::class));

        return $this->tenantContext->runAsPlatform(function () use ($actor): BackupLog {
            return DB::transaction(function () use ($actor): BackupLog {
                $backupLog = BackupLog::query()->create([
                    'school_id' => null,
                    'backup_type' => BackupLog::TYPE_MANUAL,
                    'backup_scope' => BackupLog::SCOPE_PLATFORM,
                    'status' => BackupLog::STATUS_PENDING,
                    'started_at' => now(),
                    'generated_by' => $actor->id,
                ]);

                try {
                    $relativePath = $this->exporter->exportToZip((int) $backupLog->id);
                    $backupLog->update([
                        'file_path' => $relativePath,
                        'file_size_bytes' => Storage::disk('local')->size($relativePath),
                        'status' => BackupLog::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ]);
                } catch (Throwable $exception) {
                    Log::error('Platform backup failed.', [
                        'backup_log_id' => $backupLog->id,
                        'exception' => $exception,
                    ]);

                    $backupLog->update([
                        'status' => BackupLog::STATUS_FAILED,
                        'completed_at' => now(),
                        'error_message' => 'The platform backup could not be completed.',
                    ]);

                    $this->logBackupEvidence($actor, $backupLog, 'failed');

                    return $backupLog->fresh(['generatedBy:id,name,email']);
                }

                $this->logBackupEvidence($actor, $backupLog->fresh(), 'created');

                return $backupLog->fresh(['generatedBy:id,name,email']);
            });
        });
    }

    public function downloadResponse(BackupLog $backupLog, User $actor): StreamedResponse
    {
        $this->authorizePlatformSuperAdmin($actor);
        $this->authorize($actor->can('download', $backupLog));

        $path = $backupLog->file_path;

        if (! filled($path) || ! Storage::disk('local')->exists($path)) {
            throw new AuthorizationException;
        }

        $this->tenantContext->runAsPlatform(function () use ($actor, $backupLog): void {
            $this->securityLogs->platformActivity(
                $actor,
                'backups',
                'downloaded',
                $backupLog,
                'Platform backup file downloaded.',
            );
        });

        return Storage::disk('local')->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removeBackupFile(BackupLog $backupLog, User $actor): BackupLog
    {
        $this->authorizePlatformSuperAdmin($actor);
        $this->authorize($actor->can('delete', $backupLog));

        return $this->tenantContext->runAsPlatform(function () use ($actor, $backupLog): BackupLog {
            return DB::transaction(function () use ($actor, $backupLog): BackupLog {
                $oldValues = $this->auditValues($backupLog);

                if (filled($backupLog->file_path) && Storage::disk('local')->exists($backupLog->file_path)) {
                    Storage::disk('local')->delete($backupLog->file_path);
                }

                $backupLog->update([
                    'status' => BackupLog::STATUS_DELETED,
                ]);

                $backupLog = $backupLog->fresh(['generatedBy:id,name,email']);

                $this->securityLogs->platformActivity(
                    $actor,
                    'backups',
                    'deleted',
                    $backupLog,
                    'Platform backup file removed; history retained.',
                );
                $this->securityLogs->platformAudit(
                    $actor,
                    $backupLog,
                    'deleted',
                    $oldValues,
                    $this->auditValues($backupLog),
                );

                return $backupLog;
            });
        });
    }

    private function logBackupEvidence(User $actor, BackupLog $backupLog, string $action): void
    {
        $this->securityLogs->platformActivity(
            $actor,
            'backups',
            $action,
            $backupLog,
            $action === 'created'
                ? 'Manual platform backup completed.'
                : 'Manual platform backup failed.',
        );
        $this->securityLogs->platformAudit(
            $actor,
            $backupLog,
            $action,
            [],
            $this->auditValues($backupLog),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(BackupLog $backupLog): array
    {
        return [
            'backup_type' => $backupLog->backup_type,
            'backup_scope' => $backupLog->backup_scope,
            'status' => $backupLog->status,
            'file_size_bytes' => $backupLog->file_size_bytes,
            'started_at' => $backupLog->started_at?->toDateTimeString(),
            'completed_at' => $backupLog->completed_at?->toDateTimeString(),
        ];
    }

    private function authorizePlatformSuperAdmin(User $actor): void
    {
        if (! $actor->isSuperAdmin() || ! $this->tenantContext->isPlatform()) {
            throw new AuthorizationException;
        }
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }
}
