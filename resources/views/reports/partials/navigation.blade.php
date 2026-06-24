<nav class="mb-4" aria-label="{{ __('Reports navigation') }}">
    <ul class="nav nav-pills flex-wrap gap-2">
        <li class="nav-item">
            <a class="nav-link @if (request()->routeIs('reports.index')) active @endif" href="{{ route('reports.index') }}" @if (request()->routeIs('reports.index')) aria-current="page" @endif>{{ __('Reports Hub') }}</a>
        </li>
        @can('reports.students.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('reports.students.*')) active @endif" href="{{ route('reports.students.index') }}" @if (request()->routeIs('reports.students.*')) aria-current="page" @endif>{{ __('Student Reports') }}</a>
            </li>
        @endcan
        @can('reports.attendance.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('reports.attendance.*')) active @endif" href="{{ route('reports.attendance.index') }}" @if (request()->routeIs('reports.attendance.*')) aria-current="page" @endif>{{ __('Attendance Reports') }}</a>
            </li>
        @endcan
        @can('reports.fees.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('reports.fees.*')) active @endif" href="{{ route('reports.fees.index') }}" @if (request()->routeIs('reports.fees.*')) aria-current="page" @endif>{{ __('Fee Reports') }}</a>
            </li>
        @endcan
        @can('reports.examinations.view')
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('reports.examinations.*')) active @endif" href="{{ route('reports.examinations.index') }}" @if (request()->routeIs('reports.examinations.*')) aria-current="page" @endif>{{ __('Examination Reports') }}</a>
            </li>
        @endcan
    </ul>
</nav>
