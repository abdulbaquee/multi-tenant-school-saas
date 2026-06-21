<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Students') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('students.index', array_filter(['school_id' => $selectedSchoolId])) }}</x-slot>
    <x-slot name="breadcrumb">{{ $student->admission_no }}</x-slot>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ trim($student->first_name.' '.$student->last_name) }}</h1>
                    @if ($student->trashed())<span class="badge text-bg-dark">{{ __('Archived') }}</span>@else<span class="badge {{ $student->status === \App\Models\Student::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($student->status) }}</span>@endif
                </div>
                <p class="text-body-secondary mb-0">{{ __('Admission Number: :number', ['number' => $student->admission_no]) }}</p>
            </div>
            @can('update', $student)<a class="btn btn-outline-primary align-self-start" href="{{ route('students.edit', $student) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
        </div>
    </x-slot>

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if ($privacyLimited)<div class="alert alert-info"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>{{ __('This privacy-minimized view excludes personal, guardian, contact, and photo data.') }}</div>@endif
    @if ($student->trashed())<div class="alert alert-secondary"><i class="bi bi-archive me-2" aria-hidden="true"></i>{{ __('This retained Student is archived. Restore it before activation or editing.') }}</div>@endif

    @if ($privacyLimited)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">{{ __('Authorized Student Identity') }}</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Student Name') }}</dt><dd class="col-sm-8">{{ trim($student->first_name.' '.$student->last_name) }}</dd>
                    <dt class="col-sm-4">{{ __('Admission Number') }}</dt><dd class="col-sm-8">{{ $student->admission_no }}</dd>
                    <dt class="col-sm-4">{{ __('Student State') }}</dt><dd class="col-sm-8 mb-0">{{ $student->trashed() ? __('Archived') : ucfirst($student->status) }}</dd>
                </dl>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">{{ __('Student Profile') }}</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">{{ __('Admission Number') }}</dt><dd class="col-sm-7">{{ $student->admission_no }}</dd>
                            <dt class="col-sm-5">{{ __('Admission Date') }}</dt><dd class="col-sm-7">{{ $student->admission_date->format('d M Y') }}</dd>
                            <dt class="col-sm-5">{{ __('Gender') }}</dt><dd class="col-sm-7">{{ str($student->gender)->replace('_', ' ')->title() }}</dd>
                            <dt class="col-sm-5">{{ __('Date of Birth') }}</dt><dd class="col-sm-7">{{ $student->date_of_birth->format('d M Y') }}</dd>
                            <dt class="col-sm-5">{{ __('Guardian') }}</dt><dd class="col-sm-7">{{ $student->guardian_name }}</dd>
                            <dt class="col-sm-5">{{ __('Guardian Phone') }}</dt><dd class="col-sm-7">{{ $student->guardian_phone }}</dd>
                            <dt class="col-sm-5">{{ __('Guardian Email') }}</dt><dd class="col-sm-7">{{ $student->guardian_email ?: '—' }}</dd>
                            <dt class="col-sm-5">{{ __('Residential Address') }}</dt><dd class="col-sm-7 mb-0">{{ $student->address ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">{{ __('Private Photo') }}</h2>
                        <div class="student-photo-preview border rounded-2 bg-body-tertiary d-flex align-items-center justify-content-center overflow-hidden mb-3">
                            @can('viewPhoto', $student)
                                @if ($student->photo_path)
                                    <img class="w-100 h-100 object-fit-cover" src="{{ route('students.photo.show', $student) }}" alt="{{ __('Student photo for :name', ['name' => $student->first_name]) }}">
                                @else
                                    <i class="bi bi-person-bounding-box fs-1 text-body-secondary" aria-hidden="true"></i>
                                @endif
                            @else
                                <i class="bi bi-lock fs-1 text-body-secondary" aria-hidden="true"></i>
                            @endcan
                        </div>
                        @can('updatePhoto', $student)
                            <form method="POST" action="{{ route('students.photo.update', $student) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <label for="photo" class="form-label">{{ $student->photo_path ? __('Replace Photo') : __('Upload Photo') }}</label>
                                <input id="photo" name="photo" type="file" class="form-control form-control-sm @error('photo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" required>
                                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('Private image, maximum 2 MB.') }}</div>
                                <button class="btn btn-sm btn-outline-primary mt-3" type="submit"><i class="bi bi-upload me-1" aria-hidden="true"></i>{{ $student->photo_path ? __('Replace') : __('Upload') }}</button>
                            </form>
                            @if ($student->photo_path)
                                <form class="mt-2" method="POST" action="{{ route('students.photo.destroy', $student) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash me-1" aria-hidden="true"></i>{{ __('Remove Photo') }}</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="mt-4" aria-labelledby="enrollmentHistoryHeading">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><h2 class="h5 mb-0" id="enrollmentHistoryHeading">{{ __('Enrollment History') }}</h2><span class="small text-body-secondary">{{ trans_choice(':count retained Enrollment|:count retained Enrollments', $student->enrollments->count(), ['count' => $student->enrollments->count()]) }}</span></div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">{{ __('Academic Year') }}</th><th scope="col">{{ __('Class') }}</th><th scope="col">{{ __('Section') }}</th><th scope="col">{{ __('Roll Number') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
                    <tbody>
                        @forelse ($student->enrollments as $enrollment)
                            <tr><td>{{ $enrollment->academicYear->name }}</td><td>{{ $enrollment->schoolClass->name }}</td><td>{{ $enrollment->section->name }}</td><td>{{ $enrollment->roll_no }}</td><td><span class="badge {{ $enrollment->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($enrollment->status) }}</span></td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ __('No Enrollment history is available.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="d-flex flex-wrap gap-2 mt-4">
        @can('activate', $student)<button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#activateStudentModal"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate') }}</button>@endcan
        @can('deactivate', $student)<button class="btn btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#deactivateStudentModal"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate') }}</button>@endcan
        @can('archive', $student)<button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#archiveStudentModal"><i class="bi bi-archive me-1" aria-hidden="true"></i>{{ __('Archive') }}</button>@endcan
        @can('restore', $student)<button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#restoreStudentModal"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>{{ __('Restore') }}</button>@endcan
    </div>

    @can('activate', $student)@include('academic.partials.confirmation-modal', ['modalId' => 'activateStudentModal', 'title' => __('Activate Student'), 'message' => __('This Student will become available for future operational workflows.'), 'action' => route('students.activate', $student), 'buttonLabel' => __('Activate'), 'buttonClass' => 'btn-success'])@endcan
    @can('deactivate', $student)@include('academic.partials.confirmation-modal', ['modalId' => 'deactivateStudentModal', 'title' => __('Deactivate Student'), 'message' => __('The profile remains retained, but the Student cannot enter new operational workflows.'), 'action' => route('students.deactivate', $student), 'buttonLabel' => __('Deactivate'), 'buttonClass' => 'btn-warning'])@endcan
    @can('archive', $student)@include('academic.partials.confirmation-modal', ['modalId' => 'archiveStudentModal', 'title' => __('Archive Student'), 'message' => __('Archival requires no active Enrollment and preserves identity, history, and any private photo.'), 'action' => route('students.archive', $student), 'buttonLabel' => __('Archive'), 'buttonClass' => 'btn-danger'])@endcan
    @can('restore', $student)@include('academic.partials.confirmation-modal', ['modalId' => 'restoreStudentModal', 'title' => __('Restore Student'), 'message' => __('The original record will return as inactive. Activate it separately after review.'), 'action' => route('students.restore', $student), 'buttonLabel' => __('Restore as Inactive'), 'buttonClass' => 'btn-primary'])@endcan
</x-app-layout>
