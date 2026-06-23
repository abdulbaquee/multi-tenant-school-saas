<aside id="appSidebar" class="offcanvas-lg offcanvas-start app-sidebar bg-white border-end" tabindex="-1" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <a id="appSidebarLabel" class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-2 bg-primary text-white">
                <i class="bi bi-mortarboard" aria-hidden="true"></i>
            </span>
            <span>{{ config('app.name', 'School SaaS') }}</span>
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="{{ __('Close navigation') }}"></button>
    </div>

    <div class="offcanvas-body p-0">
        <div class="d-flex flex-column h-100">
            <div class="d-none d-lg-flex align-items-center border-bottom px-3 app-sidebar-brand">
                <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-2 bg-primary text-white">
                        <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    </span>
                    <span>{{ config('app.name', 'School SaaS') }}</span>
                </a>
            </div>

            <nav class="p-3" aria-label="{{ __('Primary navigation') }}">
                <p class="sidebar-label px-2 mb-2">{{ __('Workspace') }}</p>
                <ul class="nav nav-pills flex-column gap-1">
                    @can('dashboard.view')
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\User::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}" @if (request()->routeIs('users.*')) aria-current="page" @endif>
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <span>{{ __('Users') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\Role::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('roles.*')) active @endif" href="{{ route('roles.index') }}" @if (request()->routeIs('roles.*')) aria-current="page" @endif>
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                <span>{{ __('Roles & Permissions') }}</span>
                            </a>
                        </li>
                    @endcan

                    @if (auth()->user()->can('viewAny', \App\Models\AcademicYear::class) || auth()->user()->can('viewAny', \App\Models\SchoolClass::class))
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('academic-years.*') || request()->routeIs('academic-terms.*') || request()->routeIs('teacher-profiles.*') || request()->routeIs('classes.*') || request()->routeIs('sections.*') || request()->routeIs('subjects.*')) active @endif" href="{{ auth()->user()->can('viewAny', \App\Models\AcademicYear::class) ? route('academic-years.index') : route('classes.index') }}" @if (request()->routeIs('academic-years.*') || request()->routeIs('academic-terms.*') || request()->routeIs('teacher-profiles.*') || request()->routeIs('classes.*') || request()->routeIs('sections.*') || request()->routeIs('subjects.*')) aria-current="page" @endif>
                                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                <span>{{ __('Academic Structure') }}</span>
                            </a>
                        </li>
                    @endif

                    @can('viewAny', \App\Models\Student::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('students.*')) active @endif" href="{{ route('students.index') }}" @if (request()->routeIs('students.*')) aria-current="page" @endif>
                                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                                <span>{{ __('Students') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\Attendance::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('attendance.*')) active @endif" href="{{ route('attendance.index') }}" @if (request()->routeIs('attendance.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                                <span>{{ __('Attendance') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\School::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('schools.*')) active @endif" href="{{ route('schools.index') }}" @if (request()->routeIs('schools.*')) aria-current="page" @endif>
                                <i class="bi bi-buildings" aria-hidden="true"></i>
                                <span>{{ __('Schools') }}</span>
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\SchoolSetting::class)
                        <li class="nav-item">
                            <a class="nav-link sidebar-link @if (request()->routeIs('school-settings.*')) active @endif" href="{{ route('school-settings.edit') }}" @if (request()->routeIs('school-settings.*')) aria-current="page" @endif>
                                <i class="bi bi-sliders" aria-hidden="true"></i>
                                <span>{{ __('School Settings') }}</span>
                            </a>
                        </li>
                    @endcan
                </ul>

                <p class="sidebar-label px-2 mt-4 mb-2">{{ __('Account') }}</p>
                <ul class="nav nav-pills flex-column gap-1">
                    <li class="nav-item">
                        <a class="nav-link sidebar-link @if (request()->routeIs('profile.*')) active @endif" href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <span>{{ __('Profile') }}</span>
                        </a>
                    </li>
                </ul>
            </nav>

        </div>
    </div>
</aside>
