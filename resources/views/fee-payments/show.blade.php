<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Collection') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-collections.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $feePayment->receipt_no }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Fee Receipt') }}</h1>
                <p class="text-body-secondary mb-0">{{ $feePayment->receipt_no }} · {{ $feePayment->student->admission_no }}</p>
            </div>
            <a class="btn btn-outline-primary align-self-start" href="{{ route('fee-payments.print', $feePayment) }}" target="_blank" rel="noopener">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>{{ __('Print Receipt') }}
            </a>
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    @include('fee-payments.partials.receipt-body')
</x-app-layout>
