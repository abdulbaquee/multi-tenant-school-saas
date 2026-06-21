<x-app-layout>
    <x-slot name="breadcrumbParent">{{ $assignedView ? __('Assigned Classes') : __('Classes') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('classes.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $schoolClass->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $schoolClass->name }}</h1>
                    @if ($schoolClass->trashed())<span class="badge text-bg-dark">{{ __('Archived') }}</span>@else<span class="badge {{ $schoolClass->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($schoolClass->status) }}</span>@endif
                </div>
                <p class="text-body-secondary mb-0">{{ $assignedView ? __('Assigned Class :code', ['code' => $schoolClass->code]) : __('Class :code', ['code' => $schoolClass->code]) }}</p>
            </div>
            @can('update', $schoolClass)<a class="btn btn-outline-primary align-self-start" href="{{ route('classes.edit', $schoolClass) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if ($schoolClass->trashed())<div class="alert alert-secondary"><i class="bi bi-archive me-2" aria-hidden="true"></i>{{ __('This retained Class is archived. Restore it before activation or editing.') }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><h2 class="h5 mb-3">{{ __('Class Details') }}</h2><dl class="row mb-0"><dt class="col-sm-5">{{ __('Name') }}</dt><dd class="col-sm-7">{{ $schoolClass->name }}</dd><dt class="col-sm-5">{{ __('Code') }}</dt><dd class="col-sm-7">{{ $schoolClass->code }}</dd><dt class="col-sm-5">{{ __('Sort Order') }}</dt><dd class="col-sm-7">{{ $schoolClass->sort_order }}</dd>@if (auth()->user()->isSuperAdmin())<dt class="col-sm-5">{{ __('School') }}</dt><dd class="col-sm-7">{{ $schoolClass->school->name }}</dd>@endif<dt class="col-sm-5">{{ __('State') }}</dt><dd class="col-sm-7 mb-0">{{ $schoolClass->trashed() ? __('Archived') : ucfirst($schoolClass->status) }}</dd></dl></div></div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><h2 class="h5 mb-3">{{ $assignedView ? __('Your Assignment Summary') : __('Dependency Summary') }}</h2><dl class="row mb-0"><dt class="col-sm-6">{{ $assignedView ? __('Assigned Sections') : __('Retained Sections') }}</dt><dd class="col-sm-6">{{ $schoolClass->sections->count() }}</dd><dt class="col-sm-6">{{ $assignedView ? __('Assigned Subjects') : __('Retained Subjects') }}</dt><dd class="col-sm-6">{{ $schoolClass->subjects->count() }}</dd><dt class="col-sm-6">{{ __('Active Dependencies') }}</dt><dd class="col-sm-6 mb-0">{{ $schoolClass->sections->where('status', 'active')->whereNull('deleted_at')->count() + $schoolClass->subjects->where('status', 'active')->whereNull('deleted_at')->count() }}</dd></dl></div></div>
        </div>
    </div>

    <section class="mt-4" aria-labelledby="classAssignmentsHeading">
        <h2 class="h5 mb-3" id="classAssignmentsHeading">{{ $assignedView ? __('Your Class Assignments') : __('Class Dependencies') }}</h2>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Assigned Teacher') }}</th><th scope="col">{{ __('State') }}</th></tr></thead><tbody>
            @foreach ($schoolClass->sections as $section)<tr><td>{{ __('Section') }}</td><td>{{ $section->name }}</td><td>{{ $section->teacher?->user?->name ?: '—' }}</td><td><span class="badge {{ $section->trashed() ? 'text-bg-dark' : ($section->status === 'active' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $section->trashed() ? __('Archived') : ucfirst($section->status) }}</span></td></tr>@endforeach
            @foreach ($schoolClass->subjects as $subject)<tr><td>{{ __('Subject') }}</td><td>{{ $subject->name }}</td><td>{{ $subject->teacher?->user?->name ?: '—' }}</td><td><span class="badge {{ $subject->trashed() ? 'text-bg-dark' : ($subject->status === 'active' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $subject->trashed() ? __('Archived') : ucfirst($subject->status) }}</span></td></tr>@endforeach
            @if ($schoolClass->sections->isEmpty() && $schoolClass->subjects->isEmpty())<tr><td colspan="4" class="text-center text-body-secondary py-4">{{ $assignedView ? __('No active Section or Subject assignments are connected to this Class.') : __('No Section or Subject dependencies are retained for this Class.') }}</td></tr>@endif
        </tbody></table></div></div>
    </section>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $schoolClass)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#activateClassModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate') }}</button>@endcan
        @can('deactivate', $schoolClass)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#deactivateClassModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
        @can('archive', $schoolClass)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#archiveClassModal"><i class="bi bi-archive me-1" aria-hidden="true"></i>{{ __('Archive') }}</button>@endcan
        @can('restore', $schoolClass)<button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#restoreClassModal"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('Restore') }}</button>@endcan
    </div>

    @can('activate', $schoolClass)@include('academic.partials.confirmation-modal', ['modalId' => 'activateClassModal', 'title' => __('Activate Class'), 'message' => __('This makes the Class available for active academic relationships.'), 'action' => route('classes.activate', $schoolClass), 'buttonLabel' => __('Activate'), 'buttonClass' => 'btn-success'])@endcan
    @can('deactivate', $schoolClass)@include('academic.partials.confirmation-modal', ['modalId' => 'deactivateClassModal', 'title' => __('Deactivate Class'), 'message' => __('Every active Section and Subject must be deactivated or archived first.'), 'action' => route('classes.deactivate', $schoolClass), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-warning'])@endcan
    @can('archive', $schoolClass)@include('academic.partials.confirmation-modal', ['modalId' => 'archiveClassModal', 'title' => __('Archive Class'), 'message' => __('This preserves the Class identity and all historical dependencies.'), 'action' => route('classes.archive', $schoolClass), 'buttonLabel' => __('Archive'), 'buttonClass' => 'btn-danger'])@endcan
    @can('restore', $schoolClass)@include('academic.partials.confirmation-modal', ['modalId' => 'restoreClassModal', 'title' => __('Restore Class'), 'message' => __('The original Class returns inactive. Activate it separately after review.'), 'action' => route('classes.restore', $schoolClass), 'buttonLabel' => __('Restore as Inactive'), 'buttonClass' => 'btn-primary'])@endcan
</x-app-layout>
