<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Categories') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-categories.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Create Fee Category') }}</h1><p class="text-body-secondary mb-0">{{ __('Add a tenant-owned Fee Category for structure setup.') }}</p></div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fee-categories.store') }}">
                @csrf
                @include('fee-categories.partials.form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Fee Category') }}</button>
                    <a class="btn btn-link" href="{{ route('fee-categories.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
