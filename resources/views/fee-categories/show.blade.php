<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Categories') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-categories.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $feeCategory->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $feeCategory->name }}</h1>
                    <span class="badge {{ $feeCategory->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($feeCategory->status) }}</span>
                </div>
                <p class="text-body-secondary mb-0">{{ __('Fee Category setup record for the active school.') }}</p>
            </div>
            @can('update', $feeCategory)<a class="btn btn-outline-primary align-self-start" href="{{ route('fee-categories.edit', $feeCategory) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">{{ __('Name') }}</dt><dd class="col-sm-8">{{ $feeCategory->name }}</dd>
                <dt class="col-sm-4">{{ __('Description') }}</dt><dd class="col-sm-8">{{ $feeCategory->description ?: '—' }}</dd>
                <dt class="col-sm-4">{{ __('Retained Structures') }}</dt><dd class="col-sm-8">{{ $feeCategory->fee_structures_count }}</dd>
                <dt class="col-sm-4">{{ __('State') }}</dt><dd class="col-sm-8 mb-0">{{ ucfirst($feeCategory->status) }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $feeCategory)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#activateFeeCategoryModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate') }}</button>@endcan
        @can('deactivate', $feeCategory)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#deactivateFeeCategoryModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
    </div>

    @can('activate', $feeCategory)@include('academic.partials.confirmation-modal', ['modalId' => 'activateFeeCategoryModal', 'title' => __('Activate Fee Category'), 'message' => __('This makes the category available for new Fee Structures.'), 'action' => route('fee-categories.activate', $feeCategory), 'buttonLabel' => __('Activate'), 'buttonClass' => 'btn-success'])@endcan
    @can('deactivate', $feeCategory)@include('academic.partials.confirmation-modal', ['modalId' => 'deactivateFeeCategoryModal', 'title' => __('Deactivate Fee Category'), 'message' => __('Existing structures and assignments remain retained.'), 'action' => route('fee-categories.deactivate', $feeCategory), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-warning'])@endcan
</x-app-layout>
