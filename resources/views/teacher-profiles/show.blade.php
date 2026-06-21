<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Teacher Profiles') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('teacher-profiles.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $teacher->user->name }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $teacher->user->name }}</h1>
                    @if ($teacher->trashed())
                        <span class="badge text-bg-dark">{{ __('Archived') }}</span>
                    @else
                        <span class="badge {{ $teacher->status === \App\Models\Teacher::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($teacher->status) }}</span>
                    @endif
                </div>
                <p class="text-body-secondary mb-0">{{ __('Teacher Profile :code', ['code' => $teacher->employee_code]) }}</p>
            </div>
            @can('update', $teacher)
                <a class="btn btn-outline-primary align-self-start" href="{{ route('teacher-profiles.edit', $teacher) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if ($teacher->trashed())
        <div class="alert alert-secondary"><i class="bi bi-archive me-2" aria-hidden="true"></i>{{ __('This retained profile is archived. Restore it before activation or editing.') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Academic Profile') }}</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">{{ __('Employee Code') }}</dt><dd class="col-sm-7">{{ $teacher->employee_code }}</dd>
                        <dt class="col-sm-5">{{ __('Qualification') }}</dt><dd class="col-sm-7">{{ $teacher->qualification ?: '—' }}</dd>
                        <dt class="col-sm-5">{{ __('Specialization') }}</dt><dd class="col-sm-7">{{ $teacher->specialization ?: '—' }}</dd>
                        <dt class="col-sm-5">{{ __('Academic Contact') }}</dt><dd class="col-sm-7">{{ $teacher->phone ?: '—' }}</dd>
                        <dt class="col-sm-5">{{ __('Joining Date') }}</dt><dd class="col-sm-7">{{ $teacher->joining_date?->format('d M Y') ?: '—' }}</dd>
                        @if (auth()->user()->isSuperAdmin())<dt class="col-sm-5">{{ __('School') }}</dt><dd class="col-sm-7">{{ $teacher->school->name }}</dd>@endif
                        <dt class="col-sm-5">{{ __('Profile State') }}</dt>
                        <dd class="col-sm-7 mb-0">{{ $teacher->trashed() ? __('Archived') : ucfirst($teacher->status) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Linked User Account') }}</h2>
                    <dl class="mb-0">
                        <dt>{{ __('Name') }}</dt><dd>{{ $teacher->user->name }}</dd>
                        <dt>{{ __('Email') }}</dt><dd>{{ $teacher->user->email }}</dd>
                        <dt>{{ __('Account Status') }}</dt><dd><span class="badge {{ $teacher->user->status === \App\Models\User::STATUS_ACTIVE && ! $teacher->user->trashed() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $teacher->user->trashed() ? __('Archived') : ucfirst($teacher->user->status) }}</span></dd>
                        <dt>{{ __('Role') }}</dt><dd class="mb-0">{{ $teacher->user->role->name }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <section class="mt-4" aria-labelledby="teacherAssignmentsHeading">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h2 class="h5 mb-0" id="teacherAssignmentsHeading">{{ __('Academic Assignments') }}</h2>
            <span class="small text-body-secondary">{{ trans_choice(':count retained assignment|:count retained assignments', $teacher->sections->count() + $teacher->subjects->count(), ['count' => $teacher->sections->count() + $teacher->subjects->count()]) }}</span>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">{{ __('Type') }}</th><th scope="col">{{ __('Assignment') }}</th><th scope="col">{{ __('Class') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
                    <tbody>
                        @foreach ($teacher->sections as $section)
                            <tr><td>{{ __('Section') }}</td><td>{{ $section->name }}</td><td>{{ $section->schoolClass->name }}</td><td><span class="badge {{ $section->trashed() ? 'text-bg-dark' : ($section->status === 'active' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $section->trashed() ? __('Archived') : ucfirst($section->status) }}</span></td></tr>
                        @endforeach
                        @foreach ($teacher->subjects as $subject)
                            <tr><td>{{ __('Subject') }}</td><td>{{ $subject->name }}</td><td>{{ $subject->schoolClass->name }}</td><td><span class="badge {{ $subject->trashed() ? 'text-bg-dark' : ($subject->status === 'active' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $subject->trashed() ? __('Archived') : ucfirst($subject->status) }}</span></td></tr>
                        @endforeach
                        @if ($teacher->sections->isEmpty() && $teacher->subjects->isEmpty())
                            <tr><td colspan="4" class="text-center text-body-secondary py-4">{{ __('No Section or Subject assignments are linked to this profile.') }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $teacher)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#activateTeacherModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate') }}</button>@endcan
        @can('deactivate', $teacher)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#deactivateTeacherModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
        @can('archive', $teacher)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#archiveTeacherModal"><i class="bi bi-archive me-1" aria-hidden="true"></i>{{ __('Archive') }}</button>@endcan
        @can('restore', $teacher)<button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#restoreTeacherModal"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('Restore') }}</button>@endcan
    </div>

    @can('activate', $teacher)
        @include('academic.partials.confirmation-modal', ['modalId' => 'activateTeacherModal', 'title' => __('Activate Teacher Profile'), 'message' => __('The linked user must remain an active Teacher in this school.'), 'action' => route('teacher-profiles.activate', $teacher), 'buttonLabel' => __('Activate'), 'buttonClass' => 'btn-success'])
    @endcan
    @can('deactivate', $teacher)
        @include('academic.partials.confirmation-modal', ['modalId' => 'deactivateTeacherModal', 'title' => __('Deactivate Teacher Profile'), 'message' => __('Every active Section and Subject assignment must be removed or deactivated first.'), 'action' => route('teacher-profiles.deactivate', $teacher), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-warning'])
    @endcan
    @can('archive', $teacher)
        @include('academic.partials.confirmation-modal', ['modalId' => 'archiveTeacherModal', 'title' => __('Archive Teacher Profile'), 'message' => __('This preserves the linked identity, employee code, and historical assignments.'), 'action' => route('teacher-profiles.archive', $teacher), 'buttonLabel' => __('Archive'), 'buttonClass' => 'btn-danger'])
    @endcan
    @can('restore', $teacher)
        @include('academic.partials.confirmation-modal', ['modalId' => 'restoreTeacherModal', 'title' => __('Restore Teacher Profile'), 'message' => __('The original profile will return as inactive. Activate it separately after review.'), 'action' => route('teacher-profiles.restore', $teacher), 'buttonLabel' => __('Restore as Inactive'), 'buttonClass' => 'btn-primary'])
    @endcan
</x-app-layout>
