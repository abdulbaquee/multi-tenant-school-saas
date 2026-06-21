<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Teacher Profiles') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('teacher-profiles.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Edit Teacher Profile') }}</h1><p class="text-body-secondary mb-0">{{ __('Update academic profile fields without changing linked identity.') }}</p></div></x-slot>

    @include('academic.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('teacher-profiles.update', $teacher) }}">
                @csrf
                @method('put')
                @include('teacher-profiles.partials.form')
                <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Teacher Profile') }}</button><a class="btn btn-link" href="{{ route('teacher-profiles.show', $teacher) }}">{{ __('Cancel') }}</a></div>
            </form>
        </div>
    </div>
</x-app-layout>
