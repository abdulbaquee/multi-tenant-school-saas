<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Audit Trail') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Review immutable data-change evidence within your authorized scope.') }}</p>
            </div>
            <a class="btn btn-outline-secondary align-self-start" href="{{ route('audit-logs.export', request()->query()) }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
            </a>
        </div>
    </x-slot>

    @include('system-operations.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('audit-logs.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Event, auditable type, or user') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="event">{{ __('Event') }}</label>
            <input id="event" name="event" type="text" class="form-control" value="{{ request('event') }}" maxlength="80">
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
            <a class="btn btn-outline-secondary" href="{{ route('audit-logs.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
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
                        <th scope="col">{{ __('Event') }}</th>
                        <th scope="col">{{ __('Auditable') }}</th>
                        <th scope="col">{{ __('User') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->created_at?->format('Y-m-d H:i') }}</td>
                            @if ($showSchoolColumn)
                                <td>{{ $auditLog->school?->name ?? '—' }}</td>
                            @endif
                            <td><span class="badge text-bg-secondary">{{ $auditLog->event }}</span></td>
                            <td>
                                @if ($auditLog->auditable_type)
                                    <div class="fw-semibold">{{ class_basename($auditLog->auditable_type) }}</div>
                                    <div class="small text-body-secondary">#{{ $auditLog->auditable_id }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $auditLog->user?->name ?? __('System') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('audit-logs.show', $auditLog) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showSchoolColumn ? 6 : 5 }}" class="text-center text-body-secondary py-4">{{ __('No audit records match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $auditLogs->links() }}</div>
</x-app-layout>
