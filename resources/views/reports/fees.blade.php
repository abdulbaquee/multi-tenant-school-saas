<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ $title }}</h1>
                <p class="text-body-secondary mb-0">{{ $description }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i>{{ __('Print') }}
                </button>
                @if ($canExport)
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('reports.fees.export', request()->query()) }}">
                        <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('reports.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('reports.fees.index') }}">
        <div class="col-md-3">
            <label class="form-label" for="search">{{ $mode === 'platform' ? __('School') : __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ $mode === 'platform' ? __('School name or code') : __('Student or fee category') }}">
        </div>
        @if ($mode === 'school')
            <div class="col-md-2">
                <label class="form-label" for="state">{{ __('Status') }}</label>
                <select id="state" name="state" class="form-select">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['pending', 'partial', 'paid', 'waived'] as $state)
                        <option value="{{ $state }}" @selected(request('state') === $state)>{{ ucfirst($state) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('Due from') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('Due to') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.fees.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    @include('reports.partials.summary-cards')

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    @if ($mode === 'platform')
                        <tr>
                            <th scope="col">{{ __('School Code') }}</th>
                            <th scope="col">{{ __('School Name') }}</th>
                            <th scope="col">{{ __('Assignments') }}</th>
                            <th scope="col">{{ __('Outstanding') }}</th>
                            <th scope="col">{{ __('Paid') }}</th>
                            <th scope="col">{{ __('Outstanding Balance') }}</th>
                        </tr>
                    @else
                        <tr>
                            <th scope="col">{{ __('Admission No') }}</th>
                            <th scope="col">{{ __('Student Name') }}</th>
                            <th scope="col">{{ __('Category') }}</th>
                            <th scope="col">{{ __('Payable') }}</th>
                            <th scope="col">{{ __('Paid') }}</th>
                            <th scope="col">{{ __('Balance') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Due Date') }}</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if ($mode === 'platform')
                        @forelse ($schools as $school)
                            <tr>
                                <td class="fw-semibold">{{ $school->code }}</td>
                                <td>{{ $school->name }}</td>
                                <td>{{ $school->fee_assignments_count }}</td>
                                <td>{{ $school->outstanding_assignments_count }}</td>
                                <td>{{ $school->paid_assignments_count }}</td>
                                <td>{{ number_format((float) ($school->outstanding_balance_total ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No schools match these filters.') }}</td></tr>
                        @endforelse
                    @else
                        @forelse ($studentFees as $studentFee)
                            <tr>
                                <td class="fw-semibold">{{ $studentFee->student->admission_no }}</td>
                                <td>{{ trim($studentFee->student->first_name.' '.$studentFee->student->last_name) }}</td>
                                <td>{{ $studentFee->feeStructure->feeCategory->name }}</td>
                                <td>{{ number_format((float) $studentFee->payable_amount, 2) }}</td>
                                <td>{{ number_format((float) $studentFee->paid_amount, 2) }}</td>
                                <td>{{ number_format((float) $studentFee->balance_amount, 2) }}</td>
                                <td>{{ ucfirst($studentFee->status) }}</td>
                                <td>{{ $studentFee->due_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ __('No fee assignments match these filters.') }}</td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        @if ($mode === 'platform')
            {{ $schools->links() }}
        @else
            {{ $studentFees->links() }}
        @endif
    </div>
</x-app-layout>
