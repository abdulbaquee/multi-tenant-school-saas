<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Roles & Permissions') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('roles.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ $role->name }}</x-slot>

    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">{{ $role->name }}</h1>
                <p class="text-body-secondary mb-0">{{ $role->description }}</p>
            </div>
            @can('update', $role)
                <a class="btn btn-primary" href="{{ route('roles.edit', $role) }}">
                    <i class="bi bi-sliders me-1" aria-hidden="true"></i>{{ __('Edit Mapping') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($role->code === \App\Models\Role::SUPER_ADMIN)
        <div class="alert alert-info d-flex gap-2" role="status">
            <i class="bi bi-lock-fill" aria-hidden="true"></i>
            <span>{{ __('The Super Admin mapping is fixed and cannot be edited.') }}</span>
        </div>
    @elseif (! auth()->user()->can('update', $role))
        <div class="alert alert-secondary d-flex gap-2" role="status">
            <i class="bi bi-eye" aria-hidden="true"></i>
            <span>{{ __('This effective permission mapping is read-only for your account.') }}</span>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @forelse ($permissionsByModule as $module => $permissions)
                <section class="p-3 p-lg-4 @unless($loop->last) border-bottom @endunless" aria-labelledby="module-{{ $loop->index }}">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <h2 id="module-{{ $loop->index }}" class="h5 mb-0">{{ $module }}</h2>
                        <span class="badge text-bg-light border">{{ $permissions->count() }}</span>
                    </div>
                    <div class="row g-2">
                        @foreach ($permissions as $permission)
                            <div class="col-md-6 col-xl-4">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1" aria-hidden="true"></i>
                                    <div>
                                        <div class="fw-medium">{{ $permission->name }}</div>
                                        <code class="small">{{ $permission->code }}</code>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-center text-body-secondary p-4 mb-0">{{ __('No effective permissions are assigned.') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
