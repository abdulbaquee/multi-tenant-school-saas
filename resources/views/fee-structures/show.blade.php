<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Structures') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-structures.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $feeStructure->feeCategory->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $feeStructure->feeCategory->name }}</h1>
                    <span class="badge {{ $feeStructure->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($feeStructure->status) }}</span>
                </div>
                <p class="text-body-secondary mb-0">{{ $feeStructure->schoolClass->name }} · {{ $feeStructure->academicYear->name }}</p>
            </div>
            @can('update', $feeStructure)<a class="btn btn-outline-primary align-self-start" href="{{ route('fee-structures.edit', $feeStructure) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('Category') }}</dt><dd class="col-sm-8">{{ $feeStructure->feeCategory->name }}</dd>
                <dt class="col-sm-4">{{ __('Academic Year') }}</dt><dd class="col-sm-8">{{ $feeStructure->academicYear->name }}</dd>
                <dt class="col-sm-4">{{ __('Class') }}</dt><dd class="col-sm-8">{{ $feeStructure->schoolClass->name }} ({{ $feeStructure->schoolClass->code }})</dd>
                <dt class="col-sm-4">{{ __('Amount') }}</dt><dd class="col-sm-8">{{ number_format((float) $feeStructure->amount, 2) }}</dd>
                <dt class="col-sm-4">{{ __('Frequency') }}</dt><dd class="col-sm-8">{{ str_replace('_', ' ', ucfirst($feeStructure->frequency)) }}</dd>
                <dt class="col-sm-4">{{ __('Due Date') }}</dt><dd class="col-sm-8">{{ $feeStructure->due_date?->format('Y-m-d') ?: '—' }}</dd>
                <dt class="col-sm-4">{{ __('Assignments') }}</dt><dd class="col-sm-8">{{ $feeStructure->student_fees_count }}</dd>
                <dt class="col-sm-4">{{ __('State') }}</dt><dd class="col-sm-8 mb-0">{{ ucfirst($feeStructure->status) }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $feeStructure)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#activateFeeStructureModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate') }}</button>@endcan
        @can('deactivate', $feeStructure)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#deactivateFeeStructureModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
        @can('create', \App\Models\StudentFee::class)<a class="btn btn-primary" href="{{ route('student-fees.create', ['fee_structure_id' => $feeStructure->id]) }}"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>{{ __('Assign Student Fee') }}</a>@endcan
    </div>

    @can('activate', $feeStructure)@include('academic.partials.confirmation-modal', ['modalId' => 'activateFeeStructureModal', 'title' => __('Activate Fee Structure'), 'message' => __('This makes the structure available for new Student Fee assignments.'), 'action' => route('fee-structures.activate', $feeStructure), 'buttonLabel' => __('Activate'), 'buttonClass' => 'btn-success'])@endcan
    @can('deactivate', $feeStructure)@include('academic.partials.confirmation-modal', ['modalId' => 'deactivateFeeStructureModal', 'title' => __('Deactivate Fee Structure'), 'message' => __('Existing assignments remain retained.'), 'action' => route('fee-structures.deactivate', $feeStructure), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-warning'])@endcan
</x-app-layout>
