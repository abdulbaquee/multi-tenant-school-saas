<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Roles & Permissions') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review fixed roles and their effective permissions.') }}</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Role') }}</th>
                        <th scope="col">{{ __('Access Scope') }}</th>
                        <th scope="col">{{ __('Effective Permissions') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                <div class="small text-body-secondary">{{ $role->description }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $role->code === \App\Models\Role::SUPER_ADMIN ? 'text-bg-dark' : 'text-bg-primary' }}">
                                    {{ $role->code === \App\Models\Role::SUPER_ADMIN ? __('Platform') : __('School') }}
                                </span>
                            </td>
                            <td>{{ trans_choice(':count permission|:count permissions', $role->permissions_count, ['count' => $role->permissions_count]) }}</td>
                            <td class="text-end text-nowrap">
                                @can('view', $role)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('roles.show', $role) }}">
                                        <i class="bi bi-eye me-1" aria-hidden="true"></i>{{ __('View') }}
                                    </a>
                                @endcan
                                @can('update', $role)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('roles.edit', $role) }}">
                                        <i class="bi bi-sliders me-1" aria-hidden="true"></i>{{ __('Edit Mapping') }}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-4">{{ __('No roles are available.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
