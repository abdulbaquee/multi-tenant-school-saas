<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Collection') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-collections.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $studentFee->student->admission_no }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Collect Payment') }}</h1>
            <p class="text-body-secondary mb-0">{{ $studentFee->student->admission_no }} · {{ $studentFee->feeStructure->feeCategory->name }} · {{ __('Balance') }} {{ number_format((float) $studentFee->balance_amount, 2) }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fee-collections.store', $studentFee) }}">
                @csrf
                <input type="hidden" name="collection_token" value="{{ old('collection_token', $collectionToken) }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="amount_paid">{{ __('Amount To Collect') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0.01" max="{{ $studentFee->balance_amount }}" class="form-control @error('amount_paid') is-invalid @enderror" value="{{ old('amount_paid', $studentFee->balance_amount) }}" required>
                        @error('amount_paid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="payment_date">{{ __('Payment Date') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="payment_date" name="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="payment_mode">{{ __('Payment Mode') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="payment_mode" name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror" required>
                            @foreach (['cash' => __('Cash'), 'card' => __('Card'), 'upi' => __('UPI'), 'bank_transfer' => __('Bank Transfer'), 'sandbox_gateway' => __('Sandbox Gateway')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_mode', 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="remarks">{{ __('Remarks') }}</label>
                        <textarea id="remarks" name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" maxlength="500">{{ old('remarks') }}</textarea>
                        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('collection_token')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Collect Payment') }}</button>
                    <a class="btn btn-link" href="{{ route('fee-collections.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
