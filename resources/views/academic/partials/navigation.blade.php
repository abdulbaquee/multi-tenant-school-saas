<nav class="mb-4 border-bottom" aria-label="{{ __('Academic Structure navigation') }}">
    <ul class="nav nav-tabs flex-nowrap overflow-x-auto">
        @can('viewAny', \App\Models\AcademicYear::class)
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('academic-years.*')) active @endif" href="{{ route('academic-years.index') }}" @if (request()->routeIs('academic-years.*')) aria-current="page" @endif>
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ __('Academic Years') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('academic-terms.*')) active @endif" href="{{ route('academic-terms.index') }}" @if (request()->routeIs('academic-terms.*')) aria-current="page" @endif>
                    <i class="bi bi-calendar-range me-1" aria-hidden="true"></i>{{ __('Academic Terms') }}
                </a>
            </li>
        @endcan
        @can('viewAny', \App\Models\Teacher::class)
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('teacher-profiles.*')) active @endif" href="{{ route('teacher-profiles.index') }}" @if (request()->routeIs('teacher-profiles.*')) aria-current="page" @endif>
                    <i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ __('Teacher Profiles') }}
                </a>
            </li>
        @endcan
        @can('viewAny', \App\Models\SchoolClass::class)
            <li class="nav-item">
                <a class="nav-link @if (request()->routeIs('classes.*')) active @endif" href="{{ route('classes.index') }}" @if (request()->routeIs('classes.*')) aria-current="page" @endif>
                    <i class="bi bi-grid-3x3-gap me-1" aria-hidden="true"></i>{{ __('Classes') }}
                </a>
            </li>
        @endcan
    </ul>
</nav>
