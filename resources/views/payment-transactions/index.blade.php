<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Sandbox Transactions') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review local sandbox gateway Payment Transactions for the active school.') }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('payment-transactions.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Transaction number, reference, receipt, or student') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['completed' => __('Completed'), 'pending' => __('Pending'), 'failed' => __('Failed'), 'reversed' => __('Reversed')] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
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
            <a class="btn btn-outline-secondary" href="{{ route('payment-transactions.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Transaction') }}</th>
                        <th scope="col">{{ __('Receipt') }}</th>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Amount') }}</th>
                        <th scope="col">{{ __('Processed') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentTransactions as $paymentTransaction)
                        <tr>
                            <td class="fw-semibold">{{ $paymentTransaction->transaction_no }}</td>
                            <td>{{ $paymentTransaction->feePayment->receipt_no }}</td>
                            <td>
                                <div class="fw-semibold">{{ $paymentTransaction->feePayment->student->admission_no }}</div>
                                <div class="small text-body-secondary">{{ $paymentTransaction->feePayment->student->first_name }} {{ $paymentTransaction->feePayment->student->last_name }}</div>
                            </td>
                            <td>{{ number_format((float) $paymentTransaction->amount, 2) }}</td>
                            <td>{{ $paymentTransaction->processed_at?->format('Y-m-d H:i') }}</td>
                            <td><span class="badge text-bg-secondary">{{ ucfirst($paymentTransaction->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('payment-transactions.show', $paymentTransaction) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No sandbox transactions match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $paymentTransactions->links() }}</div>
</x-app-layout>
