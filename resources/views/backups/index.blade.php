<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Backup Management') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Create and review manual platform backups stored in private storage.') }}</p>
            </div>
            @can('create', \App\Models\BackupLog::class)
                <button class="btn btn-primary align-self-start" type="button" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                    <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>{{ __('Create Backup') }}
                </button>
                <div class="modal fade" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="createBackupModalLabel">{{ __('Create Platform Backup') }}</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">{{ __('Generate a synchronous local backup archive of the platform database. This may take a moment on larger datasets.') }}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <form method="POST" action="{{ route('backups.store') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">{{ __('Create Backup') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </x-slot>

    @include('system-operations.partials.navigation')

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('backups.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('File path, error message, or user') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ([\App\Models\BackupLog::STATUS_COMPLETED => __('Completed'), \App\Models\BackupLog::STATUS_PENDING => __('Pending'), \App\Models\BackupLog::STATUS_FAILED => __('Failed'), \App\Models\BackupLog::STATUS_DELETED => __('Deleted')] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('From') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('To') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('backups.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Started') }}</th>
                        <th scope="col">{{ __('Scope') }}</th>
                        <th scope="col">{{ __('Type') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Size') }}</th>
                        <th scope="col">{{ __('Generated By') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backupLog)
                        <tr>
                            <td>{{ $backupLog->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ ucfirst($backupLog->backup_scope) }}</td>
                            <td>{{ ucfirst($backupLog->backup_type) }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($backupLog->status) }}</span></td>
                            <td>
                                @if ($backupLog->file_size_bytes)
                                    {{ number_format($backupLog->file_size_bytes / 1024, 1) }} KB
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $backupLog->generatedBy?->name ?? __('System') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('backups.show', $backupLog) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No backup history found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $backups->links() }}</div>
</x-app-layout>
