<nav class="navbar app-topbar sticky-top bg-white border-bottom" aria-label="{{ __('Account navigation') }}">
    <div class="container-fluid px-3 px-lg-4">
        <button class="btn btn-outline-secondary d-lg-none app-icon-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="{{ __('Open navigation') }}">
            <i class="bi bi-list fs-5" aria-hidden="true"></i>
        </button>

        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="d-none d-md-block text-end lh-sm">
                <span class="d-block small text-body-secondary">
                    {{ Auth::user()->isSuperAdmin() ? __('Platform') : (Auth::user()->school?->name ?? __('School')) }}
                </span>
                <span class="small fw-semibold">{{ Auth::user()->role?->name ?? __('User') }}</span>
            </div>

            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle app-account-button d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span class="app-account-name d-none d-sm-inline text-truncate">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person me-2" aria-hidden="true"></i>{{ __('Profile') }}
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>{{ __('Log Out') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
