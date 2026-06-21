<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Teacher Profiles') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage minimal academic identities linked to Teacher users.') }}</p>
            </div>
            @can('create', \App\Models\Teacher::class)
                <a class="btn btn-primary align-self-start" href="{{ route('teacher-profiles.create') }}">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>{{ __('New Teacher Profile') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('teacher-profiles.index') }}">
        <div class="col-md-7">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Name, email, employee code, or specialization') }}">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="form-label" for="state">{{ __('Profile State') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('All states') }}</option>
                <option value="active" @selected(request('state') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('state') === 'inactive')>{{ __('Inactive') }}</option>
                <option value="archived" @selected(request('state') === 'archived')>{{ __('Archived') }}</option>
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}">
                <i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span>
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('teacher-profiles.index') }}" title="{{ __('Clear filters') }}">
                <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span>
            </a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Teacher') }}</th>
                        <th scope="col">{{ __('Employee Code') }}</th>
                        @if (auth()->user()->isSuperAdmin())<th scope="col">{{ __('School') }}</th>@endif
                        <th scope="col">{{ __('Specialization') }}</th>
                        <th scope="col">{{ __('Assignments') }}</th>
                        <th scope="col">{{ __('State') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $teacher->user->name }}</div>
                                <div class="small text-body-secondary">{{ $teacher->user->email }}</div>
                            </td>
                            <td class="fw-semibold">{{ $teacher->employee_code }}</td>
                            @if (auth()->user()->isSuperAdmin())<td>{{ $teacher->school->name }}</td>@endif
                            <td>{{ $teacher->specialization ?: '—' }}</td>
                            <td>{{ trans_choice(':count assignment|:count assignments', $teacher->sections_count + $teacher->subjects_count, ['count' => $teacher->sections_count + $teacher->subjects_count]) }}</td>
                            <td>
                                @if ($teacher->trashed())
                                    <span class="badge text-bg-dark">{{ __('Archived') }}</span>
                                @else
                                    <span class="badge {{ $teacher->status === \App\Models\Teacher::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($teacher->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('teacher-profiles.show', $teacher) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('update', $teacher)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('teacher-profiles.edit', $teacher) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="text-center text-body-secondary py-4">{{ __('No Teacher Profiles match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $teachers->links() }}</div>
</x-app-layout>
