<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Student Fees') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Review tenant Student Fee assignments with privacy-minimized identifiers and balances.') }}</p>
            </div>
            @can('create', \App\Models\StudentFee::class)
                <a class="btn btn-primary align-self-start" href="{{ route('student-fees.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Assign Student Fee') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('student-fees.index') }}">
        <div class="col-md-7">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Admission number or student name') }}">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="form-label" for="state">{{ __('Assignment State') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('All states') }}</option>
                @foreach (['pending' => __('Pending'), 'partial' => __('Partial'), 'paid' => __('Paid'), 'waived' => __('Waived'), 'cancelled' => __('Cancelled')] as $value => $label)
                    <option value="{{ $value }}" @selected(request('state') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('student-fees.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
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
                            <td><span class="badge text-bg-secondary">{{ ucfirst($studentFee->status) }}</span></td>
                            <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="{{ route('student-fees.show', $studentFee) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No Student Fee assignments match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $studentFees->links() }}</div>
</x-app-layout>
