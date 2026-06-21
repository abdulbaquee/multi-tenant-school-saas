<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Academic Terms') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage ordered terms inside active academic years.') }}</p>
            </div>
            @can('create', \App\Models\AcademicTerm::class)
                <a class="btn btn-primary align-self-start" href="{{ route('academic-terms.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Academic Term') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('academic.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('academic-terms.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Academic term name') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="academic_year_id">{{ __('Academic Year') }}</label>
            <select id="academic_year_id" name="academic_year_id" class="form-select">
                <option value="">{{ __('All academic years') }}</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected((string) request('academic_year_id') === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-8 col-md-2">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('academic-terms.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Academic Term') }}</th>
                        <th scope="col">{{ __('Academic Year') }}</th>
                        @if (auth()->user()->isSuperAdmin())<th scope="col">{{ __('School') }}</th>@endif
                        <th scope="col">{{ __('Date Range') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($academicTerms as $academicTerm)
                        <tr>
                            <td><div class="fw-semibold">{{ $academicTerm->name }}</div><div class="small text-body-secondary">{{ __('Order :order', ['order' => $academicTerm->term_order]) }}</div></td>
                            <td>{{ $academicTerm->academicYear->name }}</td>
                            @if (auth()->user()->isSuperAdmin())<td>{{ $academicTerm->school->name }}</td>@endif
                            <td class="text-nowrap">{{ $academicTerm->start_date->format('d M Y') }} - {{ $academicTerm->end_date->format('d M Y') }}</td>
                            <td><span class="badge {{ $academicTerm->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($academicTerm->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('academic-terms.show', $academicTerm) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('update', $academicTerm)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('academic-terms.edit', $academicTerm) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}" class="text-center text-body-secondary py-4">{{ __('No academic terms match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $academicTerms->links() }}</div>
</x-app-layout>
