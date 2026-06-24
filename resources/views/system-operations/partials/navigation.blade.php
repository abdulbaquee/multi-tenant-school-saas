<nav class="mb-4" aria-label="{{ __('System logs navigation') }}">
    <ul class="nav nav-pills flex-wrap gap-2">
        @can('activity_logs.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('activity-logs.*')) active @endif" href="{{ route('activity-logs.index') }}" @if (request()->routeIs('activity-logs.*')) aria-current="page" @endif>{{ __('Activity Logs') }}</a>
            </li>
        @endcan
        @can('audit_logs.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('audit-logs.*')) active @endif" href="{{ route('audit-logs.index') }}" @if (request()->routeIs('audit-logs.*')) aria-current="page" @endif>{{ __('Audit Trail') }}</a>
            </li>
        @endcan
        @can('backups.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('backups.*')) active @endif" href="{{ route('backups.index') }}" @if (request()->routeIs('backups.*')) aria-current="page" @endif>{{ __('Backup Management') }}</a>
            </li>
        @endcan
    </ul>
</nav>
