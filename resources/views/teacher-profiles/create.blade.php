<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Teacher Profiles') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('teacher-profiles.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Create Teacher Profile') }}</h1><p class="text-body-secondary mb-0">{{ __('Link an existing Teacher user to minimal academic identity.') }}</p></div></x-slot>

    @include('academic.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($eligibleUsers->isEmpty())
                <div class="text-center py-4">
                    <i class="bi bi-person-exclamation fs-2 text-body-secondary" aria-hidden="true"></i>
                    <h2 class="h5 mt-3">{{ __('No eligible Teacher users') }}</h2>
                    <p class="text-body-secondary">{{ __('Create an active Teacher-role user first, or restore the existing retained profile.') }}</p>
                    @can('create', \App\Models\User::class)
                        <a class="btn btn-outline-primary" href="{{ route('users.create') }}"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>{{ __('Create User') }}</a>
                    @endcan
                </div>
            @else
                <form method="POST" action="{{ route('teacher-profiles.store') }}">
                    @csrf
                    @include('teacher-profiles.partials.form')
                    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Teacher Profile') }}</button><a class="btn btn-link" href="{{ route('teacher-profiles.index') }}">{{ __('Cancel') }}</a></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
