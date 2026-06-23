<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Sandbox Transactions') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('payment-transactions.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $paymentTransaction->transaction_no }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Sandbox Transaction') }}</h1>
                <p class="text-body-secondary mb-0">{{ $paymentTransaction->transaction_no }} · {{ $paymentTransaction->feePayment->receipt_no }}</p>
            </div>
            <a class="btn btn-outline-secondary align-self-start" href="{{ route('fee-payments.show', $paymentTransaction->feePayment) }}">
                <i class="bi bi-receipt me-1" aria-hidden="true"></i>{{ __('View Receipt') }}
            </a>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Transaction Details') }}</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Transaction No.') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->transaction_no }}</dd>
                        <dt class="col-sm-4">{{ __('Gateway Reference') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->gateway_reference ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Amount') }}</dt>
                        <dd class="col-sm-8">{{ number_format((float) $paymentTransaction->amount, 2) }}</dd>
                        <dt class="col-sm-4">{{ __('Status') }}</dt>
                        <dd class="col-sm-8">{{ ucfirst($paymentTransaction->status) }}</dd>
                        <dt class="col-sm-4">{{ __('Processed At') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->processed_at?->format('Y-m-d H:i:s') ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Linked Receipt') }}</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Receipt No.') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->feePayment->receipt_no }}</dd>
                        <dt class="col-sm-4">{{ __('Student') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->feePayment->student->admission_no }} · {{ $paymentTransaction->feePayment->student->first_name }} {{ $paymentTransaction->feePayment->student->last_name }}</dd>
                        <dt class="col-sm-4">{{ __('Fee Category') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->feePayment->studentFee->feeStructure->feeCategory->name }}</dd>
                        <dt class="col-sm-4">{{ __('Class') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->feePayment->studentFee->feeStructure->schoolClass->name }}</dd>
                        <dt class="col-sm-4">{{ __('Received By') }}</dt>
                        <dd class="col-sm-8">{{ $paymentTransaction->feePayment->receivedBy->name }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Sanitized Sandbox Payload') }}</h2>
                    @if ($sandboxPayload === [])
                        <p class="text-body-secondary mb-0">{{ __('No sanitized sandbox payload is stored for this transaction.') }}</p>
                    @else
                        <dl class="row mb-0">
                            @foreach ($sandboxPayload as $key => $value)
                                <dt class="col-sm-3">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="col-sm-9">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
