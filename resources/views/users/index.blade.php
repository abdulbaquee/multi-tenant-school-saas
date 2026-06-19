<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">{{ __('Users') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Manage platform users with role-based access.') }}</p>
            </div>

            @can('create', \App\Models\User::class)
                <a class="btn btn-primary" href="{{ route('users.create') }}">
                    <i class="bi bi-person-plus me-1"></i>{{ __('New User') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('School') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->name ?? '—' }}</td>
                            <td>{{ $user->school?->name ?? 'Platform' }}</td>
                            <td>
                                <span class="badge {{ $user->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('view', $user)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.show', $user) }}">
                                        {{ __('View') }}
                                    </a>
                                @endcan
                                @can('update', $user)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $user) }}">
                                        {{ __('Edit') }}
                                    </a>
                                @endcan
                                @can('activate', $user)
                                    <form class="d-inline" method="POST" action="{{ route('users.activate', $user) }}">
                                        @csrf
                                        @method('patch')
                                        <button class="btn btn-sm btn-outline-success" type="submit">{{ __('Activate') }}</button>
                                    </form>
                                @endcan
                                @can('deactivate', $user)
                                    <form class="d-inline" method="POST" action="{{ route('users.deactivate', $user) }}" onsubmit="return confirm('{{ __('Deactivate this user?') }}')">
                                        @csrf
                                        @method('patch')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Deactivate') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">{{ __('No users found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
</x-app-layout>
