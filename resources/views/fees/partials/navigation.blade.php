<nav class="nav nav-pills flex-wrap gap-2 mb-4" aria-label="{{ __('Fee navigation') }}">
    @can('viewAny', \App\Models\FeeCategory::class)
        <a class="nav-link @if (request()->routeIs('fee-categories.*')) active @endif" href="{{ route('fee-categories.index') }}">
            <i class="bi bi-tags me-1" aria-hidden="true"></i>{{ __('Fee Categories') }}
        </a>
    @endcan
    @can('viewAny', \App\Models\FeeStructure::class)
        <a class="nav-link @if (request()->routeIs('fee-structures.*')) active @endif" href="{{ route('fee-structures.index') }}">
            <i class="bi bi-list-columns me-1" aria-hidden="true"></i>{{ __('Fee Structures') }}
        </a>
    @endcan
    @can('viewAny', \App\Models\StudentFee::class)
        <a class="nav-link @if (request()->routeIs('student-fees.*') && ! request()->routeIs('fee-collections.*')) active @endif" href="{{ route('student-fees.index') }}">
            <i class="bi bi-receipt me-1" aria-hidden="true"></i>{{ __('Student Fees') }}
        </a>
    @endcan
    @if (auth()->user()?->hasPermission('fees.collect'))
        <a class="nav-link @if (request()->routeIs('fee-collections.*') || request()->routeIs('fee-payments.*')) active @endif" href="{{ route('fee-collections.index') }}">
            <i class="bi bi-cash-stack me-1" aria-hidden="true"></i>{{ __('Fee Collection') }}
        </a>
    @endif
</nav>
