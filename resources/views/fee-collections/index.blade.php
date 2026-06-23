<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Fee Collection') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Collect payments against open Student Fee assignments in the active school.') }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('fee-collections.index') }}">
        <div class="col-md-10">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Admission number or student name') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary w-100" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ __('Search') }}</button>
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
                            <td>{{ number_format((float) $studentFee->balance_amount, 2) }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($studentFee->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                @can('collect', $studentFee)
                                    <a class="btn btn-sm btn-primary" href="{{ route('fee-collections.create', $studentFee) }}"><i class="bi bi-cash-stack me-1" aria-hidden="true"></i>{{ __('Collect') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No collectible Student Fees match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $studentFees->links() }}</div>
</x-app-layout>
