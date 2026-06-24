<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SystemLogCsvExportService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogs,
        private readonly SystemLogCsvExportService $csvExports,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(ActivityLogIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('activity-logs.index', [
            'activityLogs' => $this->activityLogs->listFor($actor, $request->validated()),
            'showSchoolColumn' => $this->tenantContext->isPlatform(),
        ]);
    }

    public function show(Request $request, ActivityLog $activityLog): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $activityLog = $this->activityLogs->showFor($activityLog, $actor);

        return view('activity-logs.show', [
            'activityLog' => $activityLog,
            'showSchool' => $this->tenantContext->isPlatform(),
        ]);
    }

    public function export(ActivityLogIndexRequest $request): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();
        $rows = iterator_to_array($this->activityLogs->exportRowsFor($actor, $filters), false);

        return $this->csvExports->stream(
            'activity-logs-'.now()->format('Y-m-d-His').'.csv',
            $this->activityLogs->exportHeaders(),
            $rows,
        );
    }
}
