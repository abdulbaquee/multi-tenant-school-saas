<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h2 class="h4 mb-1">{{ __('Fee Receipt') }}</h2>
                <p class="text-body-secondary mb-0">{{ $feePayment->receipt_no }}</p>
            </div>
            <div class="text-end">
                <div class="fw-semibold">{{ number_format((float) $feePayment->amount_paid, 2) }}</div>
                <div class="small text-body-secondary">{{ ucfirst(str_replace('_', ' ', $feePayment->payment_mode)) }}</div>
            </div>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-4">{{ __('Payment Date') }}</dt><dd class="col-sm-8">{{ $feePayment->payment_date?->format('Y-m-d') }}</dd>
            <dt class="col-sm-4">{{ __('Admission Number') }}</dt><dd class="col-sm-8">{{ $feePayment->student->admission_no }}</dd>
            <dt class="col-sm-4">{{ __('Student Name') }}</dt><dd class="col-sm-8">{{ $feePayment->student->first_name }} {{ $feePayment->student->last_name }}</dd>
            <dt class="col-sm-4">{{ __('Fee Category') }}</dt><dd class="col-sm-8">{{ $feePayment->studentFee->feeStructure->feeCategory->name }}</dd>
            <dt class="col-sm-4">{{ __('Class') }}</dt><dd class="col-sm-8">{{ $feePayment->studentFee->feeStructure->schoolClass->name }}</dd>
            <dt class="col-sm-4">{{ __('Transaction Number') }}</dt><dd class="col-sm-8">{{ $feePayment->paymentTransactions->first()?->transaction_no ?: '—' }}</dd>
            <dt class="col-sm-4">{{ __('Received By') }}</dt><dd class="col-sm-8">{{ $feePayment->receivedBy?->name ?: '—' }}</dd>
            <dt class="col-sm-4">{{ __('Remaining Balance') }}</dt><dd class="col-sm-8 mb-0">{{ number_format((float) $feePayment->studentFee->balance_amount, 2) }}</dd>
        </dl>
    </div>
</div>
