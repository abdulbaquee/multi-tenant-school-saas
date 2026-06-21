<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ $assignedView ? __('Assigned Classes') : __('Classes') }}</h1>
                <p class="text-body-secondary mb-0">{{ $assignedView ? __('View active Classes connected to your Section or Subject assignments.') : __('Manage ordered Classes and their lifecycle state.') }}</p>
            </div>
            @can('create', \App\Models\SchoolClass::class)
                <a class="btn btn-primary align-self-start" href="{{ route('classes.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Class') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('classes.index') }}">
        <div class="{{ $assignedView ? 'col-sm-10' : 'col-md-7' }}">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Class name or code') }}">
        </div>
        @unless ($assignedView)
            <div class="col-sm-8 col-md-3">
                <label class="form-label" for="state">{{ __('Class State') }}</label>
                <select id="state" name="state" class="form-select">
                    <option value="">{{ __('All states') }}</option>
                    <option value="active" @selected(request('state') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(request('state') === 'inactive')>{{ __('Inactive') }}</option>
                    <option value="archived" @selected(request('state') === 'archived')>{{ __('Archived') }}</option>
                </select>
            </div>
        @endunless
        <div class="{{ $assignedView ? 'col-sm-2' : 'col-sm-4 col-md-2' }} d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('classes.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">{{ __('Class') }}</th>@if (auth()->user()->isSuperAdmin())<th scope="col">{{ __('School') }}</th>@endif<th scope="col">{{ __('Order') }}</th><th scope="col">{{ __('Sections') }}</th><th scope="col">{{ __('Subjects') }}</th><th scope="col">{{ __('State') }}</th><th scope="col" class="text-end">{{ __('Actions') }}</th></tr></thead>
                <tbody>
                    @forelse ($classes as $schoolClass)
                        <tr>
                            <td><div class="fw-semibold">{{ $schoolClass->name }}</div><div class="small text-body-secondary">{{ $schoolClass->code }}</div></td>
                            @if (auth()->user()->isSuperAdmin())<td>{{ $schoolClass->school->name }}</td>@endif
                            <td>{{ $schoolClass->sort_order }}</td>
                            <td>{{ $schoolClass->sections_count }}</td>
                            <td>{{ $schoolClass->subjects_count }}</td>
                            <td>
                                @if ($schoolClass->trashed())<span class="badge text-bg-dark">{{ __('Archived') }}</span>@else<span class="badge {{ $schoolClass->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($schoolClass->status) }}</span>@endif
                            </td>
                            <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="{{ route('classes.show', $schoolClass) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>@can('update', $schoolClass) <a class="btn btn-sm btn-outline-primary" href="{{ route('classes.edit', $schoolClass) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}" class="text-center text-body-secondary py-4">{{ $assignedView ? __('No active Classes are currently assigned to you.') : __('No Classes match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $classes->links() }}</div>
</x-app-layout>
