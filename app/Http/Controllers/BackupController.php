<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupIndexRequest;
use App\Models\BackupLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(BackupIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('backups.index', [
            'backups' => $this->backups->listFor($actor, $request->validated()),
        ]);
    }

    public function show(Request $request, BackupLog $backupLog): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('backups.show', [
            'backupLog' => $this->backups->showFor($backupLog, $actor),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('create', BackupLog::class);

        $backupLog = $this->backups->createManualPlatformBackup($actor);

        if ($backupLog->status === BackupLog::STATUS_FAILED) {
            return redirect()
                ->route('backups.show', $backupLog)
                ->withErrors(['backup' => __('The platform backup could not be completed. Review the backup details for the failure reason.')]);
        }

        return redirect()
            ->route('backups.show', $backupLog)
            ->with('status', __('Platform backup created successfully.'));
    }

    public function download(Request $request, BackupLog $backupLog): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return $this->backups->downloadResponse($backupLog, $actor);
    }

    public function destroy(Request $request, BackupLog $backupLog): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->backups->removeBackupFile($backupLog, $actor);

        return redirect()
            ->route('backups.show', $backupLog)
            ->with('status', __('Backup file removed. History has been retained.'));
    }
}
