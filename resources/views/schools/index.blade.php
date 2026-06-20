<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Schools') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage tenant schools and their access state.') }}</p>
            </div>

            @can('create', \App\Models\School::class)
                <a class="btn btn-primary" href="{{ route('schools.create') }}">
                    <i class="bi bi-building-add me-1" aria-hidden="true"></i>{{ __('New School') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('schools.index') }}">
        <div class="col-md-6 col-xl-5">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input id="search" name="search" type="search" class="form-control @error('search') is-invalid @enderror" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Name, code, email, or city') }}">
            </div>
            @error('search')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-3 col-xl-2">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-auto d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit">{{ __('Apply') }}</button>
            @if (request()->filled('search') || request()->filled('status'))
                <a class="btn btn-link" href="{{ route('schools.index') }}">{{ __('Clear') }}</a>
            @endif
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('School') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Location') }}</th>
                        <th>{{ __('Users') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schools as $school)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $school->name }}</div>
                                <div class="small text-body-secondary">{{ $school->email }}</div>
                            </td>
                            <td>{{ $school->code }}</td>
                            <td>{{ collect([$school->city, $school->state])->filter()->join(', ') ?: '—' }}</td>
                            <td>{{ $school->users_count }}</td>
                            <td>
                                <span class="badge {{ $school->status === \App\Models\School::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($school->status) }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('view', $school)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('schools.show', $school) }}" title="{{ __('View school') }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">{{ __('View') }}</span>
                                    </a>
                                @endcan
                                @can('update', $school)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('schools.edit', $school) }}" title="{{ __('Edit school') }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">{{ __('Edit') }}</span>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">{{ __('No schools found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $schools->links() }}
    </div>
</x-app-layout>
