<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Payment History') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review retained Fee Payment receipts for the active school.') }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('fee-payments.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Receipt number, admission number, or student name') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="payment_mode">{{ __('Payment Mode') }}</label>
            <select id="payment_mode" name="payment_mode" class="form-select">
                <option value="">{{ __('All modes') }}</option>
                @foreach (['cash' => __('Cash'), 'card' => __('Card'), 'upi' => __('UPI'), 'bank_transfer' => __('Bank Transfer'), 'sandbox_gateway' => __('Sandbox Gateway')] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_mode') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('From') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('To') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('fee-payments.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Receipt') }}</th>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Fee Category') }}</th>
                        <th scope="col">{{ __('Amount') }}</th>
                        <th scope="col">{{ __('Date') }}</th>
                        <th scope="col">{{ __('Mode') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feePayments as $feePayment)
                        <tr>
                            <td class="fw-semibold">{{ $feePayment->receipt_no }}</td>
                            <td>
                                <div class="fw-semibold">{{ $feePayment->student->admission_no }}</div>
                                <div class="small text-body-secondary">{{ $feePayment->student->first_name }} {{ $feePayment->student->last_name }}</div>
                            </td>
                            <td>{{ $feePayment->studentFee->feeStructure->feeCategory->name }}</td>
                            <td>{{ number_format((float) $feePayment->amount_paid, 2) }}</td>
                            <td>{{ $feePayment->payment_date?->format('Y-m-d') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $feePayment->payment_mode)) }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('fee-payments.show', $feePayment) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No payment history matches these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $feePayments->links() }}</div>
</x-app-layout>
