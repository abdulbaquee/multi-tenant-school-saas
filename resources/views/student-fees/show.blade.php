<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Student Fees') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('student-fees.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $studentFee->student->admission_no }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Student Fee Assignment') }}</h1>
            <p class="text-body-secondary mb-0">{{ $studentFee->student->admission_no }} · {{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}</p>
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('Admission Number') }}</dt><dd class="col-sm-8">{{ $studentFee->student->admission_no }}</dd>
                <dt class="col-sm-4">{{ __('Student Name') }}</dt><dd class="col-sm-8">{{ $studentFee->student->first_name }} {{ $studentFee->student->last_name }}</dd>
                <dt class="col-sm-4">{{ __('Fee Category') }}</dt><dd class="col-sm-8">{{ $studentFee->feeStructure->feeCategory->name }}</dd>
                <dt class="col-sm-4">{{ __('Academic Year') }}</dt><dd class="col-sm-8">{{ $studentFee->academicYear->name }}</dd>
                <dt class="col-sm-4">{{ __('Class') }}</dt><dd class="col-sm-8">{{ $studentFee->feeStructure->schoolClass->name }}</dd>
                <dt class="col-sm-4">{{ __('Assigned Amount') }}</dt><dd class="col-sm-8">{{ number_format((float) $studentFee->amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Discount') }}</dt><dd class="col-sm-8">{{ number_format((float) $studentFee->discount_amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Payable Amount') }}</dt><dd class="col-sm-8">{{ number_format((float) $studentFee->payable_amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Paid Amount') }}</dt><dd class="col-sm-8">{{ number_format((float) $studentFee->paid_amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Balance Amount') }}</dt><dd class="col-sm-8">{{ number_format((float) $studentFee->balance_amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Due Date') }}</dt><dd class="col-sm-8">{{ $studentFee->due_date?->format('Y-m-d') ?: '—' }}</dd>
                <dt class="col-sm-4">{{ __('State') }}</dt><dd class="col-sm-8 mb-0">{{ ucfirst($studentFee->status) }}</dd>
            </dl>
        </div>
    </div>
</x-app-layout>
