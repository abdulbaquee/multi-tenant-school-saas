<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Activity Logs') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Review immutable user activity within your authorized scope.') }}</p>
            </div>
            <a class="btn btn-outline-secondary align-self-start" href="{{ route('activity-logs.export', request()->query()) }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
            </a>
        </div>
    </x-slot>

    @include('system-operations.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('activity-logs.index') }}">
        <div class="col-md-3">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Module, action, description, or user') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="module">{{ __('Module') }}</label>
            <input id="module" name="module" type="text" class="form-control" value="{{ request('module') }}" maxlength="80">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="action">{{ __('Action') }}</label>
            <input id="action" name="action" type="text" class="form-control" value="{{ request('action') }}" maxlength="80">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('From') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('To') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('activity-logs.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Recorded') }}</th>
                        @if ($showSchoolColumn)
                            <th scope="col">{{ __('School') }}</th>
                        @endif
                        <th scope="col">{{ __('Module') }}</th>
                        <th scope="col">{{ __('Action') }}</th>
                        <th scope="col">{{ __('User') }}</th>
                        <th scope="col">{{ __('Description') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityLogs as $activityLog)
                        <tr>
                            <td>{{ $activityLog->created_at?->format('Y-m-d H:i') }}</td>
                            @if ($showSchoolColumn)
                                <td>{{ $activityLog->school?->name ?? '—' }}</td>
                            @endif
                            <td>{{ $activityLog->module }}</td>
                            <td><span class="badge text-bg-secondary">{{ $activityLog->action }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $activityLog->user?->name ?? __('System') }}</div>
                                @if ($activityLog->user?->email)
                                    <div class="small text-body-secondary">{{ $activityLog->user->email }}</div>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 16rem;">{{ $activityLog->description ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('activity-logs.show', $activityLog) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showSchoolColumn ? 7 : 6 }}" class="text-center text-body-secondary py-4">{{ __('No activity logs match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $activityLogs->links() }}</div>
</x-app-layout>
