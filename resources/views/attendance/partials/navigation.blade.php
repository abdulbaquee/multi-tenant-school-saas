<nav class="nav nav-tabs mb-4" aria-label="{{ __('Attendance views') }}">
    <a class="nav-link @if (request()->routeIs('attendance.index') || request()->routeIs('attendance.edit')) active @endif" href="{{ route('attendance.index') }}" @if (request()->routeIs('attendance.index') || request()->routeIs('attendance.edit')) aria-current="page" @endif>
        <i class="bi bi-calendar2-check me-1" aria-hidden="true"></i>{{ __('Entry') }}
    </a>
    <a class="nav-link @if (request()->routeIs('attendance.history')) active @endif" href="{{ route('attendance.history') }}" @if (request()->routeIs('attendance.history')) aria-current="page" @endif>
        <i class="bi bi-clock-history me-1" aria-hidden="true"></i>{{ __('History') }}
    </a>
    <a class="nav-link @if (request()->routeIs('attendance.monthly-summary')) active @endif" href="{{ route('attendance.monthly-summary') }}" @if (request()->routeIs('attendance.monthly-summary')) aria-current="page" @endif>
        <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ __('Monthly Summary') }}
    </a>
</nav>
