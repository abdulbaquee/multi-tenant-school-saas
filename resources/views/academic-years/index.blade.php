<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Academic Years') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage school-year date ranges and the current academic context.') }}</p>
            </div>
            @can('create', \App\Models\AcademicYear::class)
                <a class="btn btn-primary align-self-start" href="{{ route('academic-years.create') }}">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Academic Year') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('academic-years.index') }}">
        <div class="col-md-5">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Academic year name') }}">
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label" for="is_current">{{ __('Current') }}</label>
            <select id="is_current" name="is_current" class="form-select">
                <option value="">{{ __('All') }}</option>
                <option value="1" @selected(request('is_current') === '1')>{{ __('Current only') }}</option>
                <option value="0" @selected(request('is_current') === '0')>{{ __('Not current') }}</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}">
                <i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span>
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('academic-years.index') }}" title="{{ __('Clear filters') }}">
                <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span>
            </a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Academic Year') }}</th>
                        @if (auth()->user()->isSuperAdmin())
                            <th scope="col">{{ __('School') }}</th>
                        @endif
                        <th scope="col">{{ __('Date Range') }}</th>
                        <th scope="col">{{ __('Terms') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($academicYears as $academicYear)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $academicYear->name }}</div>
                                @if ($academicYear->is_current)
                                    <span class="badge text-bg-primary">{{ __('Current') }}</span>
                                @endif
                            </td>
                            @if (auth()->user()->isSuperAdmin())
                                <td>{{ $academicYear->school->name }}</td>
                            @endif
                            <td class="text-nowrap">{{ $academicYear->start_date->format('d M Y') }} - {{ $academicYear->end_date->format('d M Y') }}</td>
                            <td>{{ $academicYear->terms_count }}</td>
                            <td>
                                <span class="badge {{ $academicYear->status === \App\Models\AcademicYear::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($academicYear->status) }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('academic-years.show', $academicYear) }}">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}
                                </a>
                                @can('update', $academicYear)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('academic-years.edit', $academicYear) }}">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="text-center text-body-secondary py-4">{{ __('No academic years match these filters.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $academicYears->links() }}</div>
</x-app-layout>
