<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Outstanding Balances') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review open Student Fee balances for the active school.') }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">{{ __('Open Assignments') }}</div>
                    <div class="h4 mb-0">{{ $summary['assignment_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">{{ __('Pending') }}</div>
                    <div class="h4 mb-0">{{ $summary['pending_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">{{ __('Partial') }}</div>
                    <div class="h4 mb-0">{{ $summary['partial_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">{{ __('Total Outstanding') }}</div>
                    <div class="h4 mb-0">{{ $summary['total_outstanding'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('fee-outstanding-balances.index') }}">
        <div class="col-md-7">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Admission number or student name') }}">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="form-label" for="state">{{ __('Balance State') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('All open states') }}</option>
                <option value="pending" @selected(request('state') === 'pending')>{{ __('Pending') }}</option>
                <option value="partial" @selected(request('state') === 'partial')>{{ __('Partial') }}</option>
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('fee-outstanding-balances.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Fee Category') }}</th>
                        <th scope="col">{{ __('Class') }}</th>
                        <th scope="col">{{ __('Payable') }}</th>
                        <th scope="col">{{ __('Balance') }}</th>
                        <th scope="col">{{ __('Due Date') }}</th>
                        <th scope="col">{{ __('State') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($studentFees as $studentFee)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $studentFee->student->admission_no }}</div>
                                <div class="small text-body-secondary">{{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}</div>
                            </td>
                            <td>{{ $studentFee->feeStructure->feeCategory->name }}</td>
                            <td>{{ $studentFee->feeStructure->schoolClass->name }}</td>
                            <td>{{ number_format((float) $studentFee->payable_amount, 2) }}</td>
                            <td>{{ number_format((float) $studentFee->balance_amount, 2) }}</td>
                            <td>{{ $studentFee->due_date?->format('Y-m-d') ?: '—' }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($studentFee->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('student-fees.show', $studentFee) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('collect', $studentFee)
                                    <a class="btn btn-sm btn-primary" href="{{ route('fee-collections.create', $studentFee) }}"><i class="bi bi-cash-stack me-1" aria-hidden="true"></i>{{ __('Collect') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No outstanding balances match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $studentFees->links() }}</div>
</x-app-layout>
