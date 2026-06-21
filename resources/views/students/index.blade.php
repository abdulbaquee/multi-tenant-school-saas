<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Students') }}</h1>
                <p class="text-body-secondary mb-0">{{ $privacyLimited ? __('View authorized Student identities and current academic placement.') : __('Manage Student profiles, privacy, and lifecycle state.') }}</p>
            </div>
            @can('create', \App\Models\Student::class)
                <a class="btn btn-primary align-self-start" href="{{ route('students.create') }}"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>{{ __('Register Student') }}</a>
            @endcan
        </div>
    </x-slot>

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('students.index') }}">
        @if (auth()->user()->isSuperAdmin())
            <div class="col-lg-4">
                <label class="form-label" for="school_id">{{ __('School') }} <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="school_id" name="school_id" class="form-select @error('school_id') is-invalid @enderror">
                    <option value="">{{ __('Select a school') }}</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected($selectedSchoolId === $school->id)>{{ $school->name }} ({{ $school->code }})</option>
                    @endforeach
                </select>
                @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endif
        <div class="{{ auth()->user()->isSuperAdmin() ? 'col-lg-4' : ($teacherView ? 'col-md-10' : 'col-md-7') }}">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Name or admission number') }}">
        </div>
        @unless ($teacherView)
            <div class="col-sm-8 {{ auth()->user()->isSuperAdmin() ? 'col-lg-2' : 'col-md-3' }}">
                <label class="form-label" for="state">{{ __('Student State') }}</label>
                <select id="state" name="state" class="form-select">
                    <option value="">{{ __('All states') }}</option>
                    @foreach (['active' => __('Active'), 'inactive' => __('Inactive'), 'transferred' => __('Transferred'), 'graduated' => __('Graduated'), 'archived' => __('Archived')] as $value => $label)
                        <option value="{{ $value }}" @selected(request('state') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('students.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    @if (auth()->user()->isSuperAdmin() && $selectedSchoolId === null)
        <div class="alert alert-info d-flex align-items-center gap-2" role="status"><i class="bi bi-buildings" aria-hidden="true"></i>{{ __('Select one school to view its privacy-minimized Student directory.') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th scope="col">{{ __('Student') }}</th><th scope="col">{{ __('Admission Number') }}</th><th scope="col">{{ __('Current Placement') }}</th><th scope="col">{{ __('State') }}</th><th scope="col" class="text-end">{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php($enrollment = $student->enrollments->first())
                        <tr>
                            <td class="fw-semibold">{{ trim($student->first_name.' '.$student->last_name) }}</td>
                            <td>{{ $student->admission_no }}</td>
                            <td>
                                @if ($enrollment)
                                    <div>{{ $enrollment->schoolClass->name }} / {{ $enrollment->section->name }}</div>
                                    <div class="small text-body-secondary">{{ $enrollment->academicYear->name }} · {{ __('Roll :roll', ['roll' => $enrollment->roll_no]) }}</div>
                                @else
                                    <span class="text-body-secondary">{{ __('Not enrolled') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($student->trashed())
                                    <span class="badge text-bg-dark">{{ __('Archived') }}</span>
                                @else
                                    <span class="badge {{ $student->status === \App\Models\Student::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($student->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('students.show', array_filter(['student' => $student, 'school_id' => $selectedSchoolId])) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('update', $student)<a class="btn btn-sm btn-outline-primary" href="{{ route('students.edit', $student) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ auth()->user()->isSuperAdmin() && $selectedSchoolId === null ? __('No school selected.') : __('No Students match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $students->links() }}</div>
</x-app-layout>
