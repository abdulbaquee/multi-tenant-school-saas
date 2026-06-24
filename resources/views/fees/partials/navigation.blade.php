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
    @can('viewAny', \App\Models\FeePayment::class)
        <a class="nav-link @if (request()->routeIs('fee-payments.*')) active @endif" href="{{ route('fee-payments.index') }}">
            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>{{ __('Payment History') }}
        </a>
    @endcan
    @can('viewOutstandingAny', \App\Models\StudentFee::class)
        <a class="nav-link @if (request()->routeIs('fee-outstanding-balances.*')) active @endif" href="{{ route('fee-outstanding-balances.index') }}">
            <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ __('Outstanding Balances') }}
        </a>
    @endcan
    @can('viewAny', \App\Models\PaymentTransaction::class)
        <a class="nav-link @if (request()->routeIs('payment-transactions.*')) active @endif" href="{{ route('payment-transactions.index') }}">
            <i class="bi bi-credit-card-2-front me-1" aria-hidden="true"></i>{{ __('Sandbox Transactions') }}
        </a>
    @endcan
    @can('collectAny', \App\Models\StudentFee::class)
        <a class="nav-link @if (request()->routeIs('fee-collections.*')) active @endif" href="{{ route('fee-collections.index') }}">
            <i class="bi bi-cash-stack me-1" aria-hidden="true"></i>{{ __('Fee Collection') }}
        </a>
    @endcan
</nav>
