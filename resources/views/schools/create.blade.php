<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Schools') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('schools.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create School') }}</x-slot>

    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Create School') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Register a new tenant school and its default settings.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('schools.store') }}">
                @csrf
                @include('schools.partials.form')

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-building-add me-1" aria-hidden="true"></i>{{ __('Save School') }}
                    </button>
                    <a class="btn btn-link" href="{{ route('schools.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
