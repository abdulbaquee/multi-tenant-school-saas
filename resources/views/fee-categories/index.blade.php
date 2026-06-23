<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Fee Categories') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage tenant Fee Categories and lifecycle state.') }}</p>
            </div>
            @can('create', \App\Models\FeeCategory::class)
                <a class="btn btn-primary align-self-start" href="{{ route('fee-categories.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New Fee Category') }}</a>
            @endcan
        </div>
    </x-slot>

    @include('fees.partials.navigation')
    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('fee-categories.index') }}">
        <div class="col-md-7">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Category name or description') }}">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="form-label" for="state">{{ __('Category State') }}</label>
            <select id="state" name="state" class="form-select">
                <option value="">{{ __('All states') }}</option>
                <option value="active" @selected(request('state') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('state') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="col-sm-4 col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('fee-categories.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Category') }}</th>
                        <th scope="col">{{ __('Structures') }}</th>
                        <th scope="col">{{ __('State') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feeCategories as $feeCategory)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $feeCategory->name }}</div>
                                @if ($feeCategory->description)<div class="small text-body-secondary">{{ $feeCategory->description }}</div>@endif
                            </td>
                            <td>{{ $feeCategory->fee_structures_count }}</td>
                            <td><span class="badge {{ $feeCategory->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($feeCategory->status) }}</span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('fee-categories.show', $feeCategory) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}</a>
                                @can('update', $feeCategory)<a class="btn btn-sm btn-outline-primary" href="{{ route('fee-categories.edit', $feeCategory) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">{{ __('No Fee Categories match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $feeCategories->links() }}</div>
</x-app-layout>
