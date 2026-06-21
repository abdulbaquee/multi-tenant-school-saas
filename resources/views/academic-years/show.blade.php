<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Academic Years') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('academic-years.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $academicYear->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ $academicYear->name }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Academic year details and term lifecycle.') }}</p>
            </div>
            @can('update', $academicYear)
                <a class="btn btn-outline-primary align-self-start" href="{{ route('academic-years.edit', $academicYear) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Year Details') }}</h2>
                    <dl class="row mb-0">
                        @if (auth()->user()->isSuperAdmin())
                            <dt class="col-sm-5">{{ __('School') }}</dt><dd class="col-sm-7">{{ $academicYear->school->name }}</dd>
                        @endif
                        <dt class="col-sm-5">{{ __('Start Date') }}</dt><dd class="col-sm-7">{{ $academicYear->start_date->format('d M Y') }}</dd>
                        <dt class="col-sm-5">{{ __('End Date') }}</dt><dd class="col-sm-7">{{ $academicYear->end_date->format('d M Y') }}</dd>
                        <dt class="col-sm-5">{{ __('Current') }}</dt><dd class="col-sm-7">{{ $academicYear->is_current ? __('Yes') : __('No') }}</dd>
                        <dt class="col-sm-5">{{ __('Status') }}</dt>
                        <dd class="col-sm-7 mb-0"><span class="badge {{ $academicYear->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($academicYear->status) }}</span></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h5 mb-0">{{ __('Academic Terms') }}</h2>
                @can('create', \App\Models\AcademicTerm::class)
                    @if ($academicYear->status === \App\Models\AcademicYear::STATUS_ACTIVE)
                        <a class="btn btn-sm btn-primary" href="{{ route('academic-terms.create', ['academic_year_id' => $academicYear->id]) }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Term') }}</a>
                    @endif
                @endcan
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ __('Term') }}</th><th>{{ __('Dates') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Action') }}</th></tr></thead>
                        <tbody>
                            @forelse ($academicYear->terms as $term)
                                <tr>
                                    <td><span class="fw-semibold">{{ $term->name }}</span><div class="small text-body-secondary">{{ __('Order :order', ['order' => $term->term_order]) }}</div></td>
                                    <td class="text-nowrap">{{ $term->start_date->format('d M Y') }} - {{ $term->end_date->format('d M Y') }}</td>
                                    <td><span class="badge {{ $term->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($term->status) }}</span></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('academic-terms.show', $term) }}">{{ __('View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-4">{{ __('No terms have been added to this year.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $academicYear)
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#activateYearModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Set as Current') }}</button>
        @endcan
        @can('deactivate', $academicYear)
            <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deactivateYearModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>
        @endcan
        @can('reactivate', $academicYear)
            <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#reactivateYearModal"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('Reactivate') }}</button>
        @endcan
    </div>

    @can('activate', $academicYear)
        @include('academic.partials.confirmation-modal', ['modalId' => 'activateYearModal', 'title' => __('Set Current Academic Year'), 'message' => __('This will replace the current academic year for this school.'), 'action' => route('academic-years.activate', $academicYear), 'buttonLabel' => __('Set as Current'), 'buttonClass' => 'btn-primary'])
    @endcan
    @can('deactivate', $academicYear)
        @include('academic.partials.confirmation-modal', ['modalId' => 'deactivateYearModal', 'title' => __('Deactivate Academic Year'), 'message' => __('The year must not be current and all of its terms must already be inactive.'), 'action' => route('academic-years.deactivate', $academicYear), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-danger'])
    @endcan
    @can('reactivate', $academicYear)
        @include('academic.partials.confirmation-modal', ['modalId' => 'reactivateYearModal', 'title' => __('Reactivate Academic Year'), 'message' => __('This restores active status but does not make the year current.'), 'action' => route('academic-years.reactivate', $academicYear), 'buttonLabel' => __('Reactivate'), 'buttonClass' => 'btn-success'])
    @endcan
</x-app-layout>
