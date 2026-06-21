<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Academic Terms') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('academic-terms.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $academicTerm->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div><h1 class="h3 mb-1">{{ $academicTerm->name }}</h1><p class="text-body-secondary mb-0">{{ __('Academic term details and lifecycle state.') }}</p></div>
            @can('update', $academicTerm)<a class="btn btn-outline-primary align-self-start" href="{{ route('academic-terms.edit', $academicTerm) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>
    @include('academic.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('Term Details') }}</h2>
            <dl class="row mb-0">
                @if (auth()->user()->isSuperAdmin())<dt class="col-sm-4 col-lg-3">{{ __('School') }}</dt><dd class="col-sm-8 col-lg-9">{{ $academicTerm->school->name }}</dd>@endif
                <dt class="col-sm-4 col-lg-3">{{ __('Academic Year') }}</dt><dd class="col-sm-8 col-lg-9"><a href="{{ route('academic-years.show', $academicTerm->academicYear) }}">{{ $academicTerm->academicYear->name }}</a></dd>
                <dt class="col-sm-4 col-lg-3">{{ __('Term Order') }}</dt><dd class="col-sm-8 col-lg-9">{{ $academicTerm->term_order }}</dd>
                <dt class="col-sm-4 col-lg-3">{{ __('Start Date') }}</dt><dd class="col-sm-8 col-lg-9">{{ $academicTerm->start_date->format('d M Y') }}</dd>
                <dt class="col-sm-4 col-lg-3">{{ __('End Date') }}</dt><dd class="col-sm-8 col-lg-9">{{ $academicTerm->end_date->format('d M Y') }}</dd>
                <dt class="col-sm-4 col-lg-3">{{ __('Status') }}</dt><dd class="col-sm-8 col-lg-9 mb-0"><span class="badge {{ $academicTerm->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($academicTerm->status) }}</span></dd>
            </dl>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('deactivate', $academicTerm)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deactivateTermModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
        @can('reactivate', $academicTerm)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#reactivateTermModal"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('Reactivate') }}</button>@endcan
    </div>

    @can('deactivate', $academicTerm)
        @include('academic.partials.confirmation-modal', ['modalId' => 'deactivateTermModal', 'title' => __('Deactivate Academic Term'), 'message' => __('This preserves the term and its history while preventing active use.'), 'action' => route('academic-terms.deactivate', $academicTerm), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-danger'])
    @endcan
    @can('reactivate', $academicTerm)
        @include('academic.partials.confirmation-modal', ['modalId' => 'reactivateTermModal', 'title' => __('Reactivate Academic Term'), 'message' => __('The parent academic year must be active and the dates must remain valid.'), 'action' => route('academic-terms.reactivate', $academicTerm), 'buttonLabel' => __('Reactivate'), 'buttonClass' => 'btn-success'])
    @endcan
</x-app-layout>
