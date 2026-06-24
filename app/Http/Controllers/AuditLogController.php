<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogIndexRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SystemLogCsvExportService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
        private readonly SystemLogCsvExportService $csvExports,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(AuditLogIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('audit-logs.index', [
            'auditLogs' => $this->auditLogs->listFor($actor, $request->validated()),
            'showSchoolColumn' => $this->tenantContext->isPlatform(),
        ]);
    }

    public function show(Request $request, AuditLog $auditLog): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $auditLog = $this->auditLogs->showFor($auditLog, $actor);

        return view('audit-logs.show', [
            'auditLog' => $auditLog,
            'oldValues' => $this->auditLogs->sanitizedOldValues($auditLog),
            'newValues' => $this->auditLogs->sanitizedNewValues($auditLog),
            'showSchool' => $this->tenantContext->isPlatform(),
        ]);
    }

    public function export(AuditLogIndexRequest $request): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();
        $rows = iterator_to_array($this->auditLogs->exportRowsFor($actor, $filters), false);

        return $this->csvExports->stream(
            'audit-logs-'.now()->format('Y-m-d-His').'.csv',
            $this->auditLogs->exportHeaders(),
            $rows,
        );
    }
}
