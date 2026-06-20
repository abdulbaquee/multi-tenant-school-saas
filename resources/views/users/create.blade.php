<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Users') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('users.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create User') }}</x-slot>

    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Create User') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Add a platform user with an approved role.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users.partials.form')
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">{{ __('Save User') }}</button>
                    <a class="btn btn-link" href="{{ route('users.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
