<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Academic Years') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('academic-years.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Create Academic Year') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Add a non-overlapping school year. It will start as active and not current.') }}</p>
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('academic-years.store') }}">
                @csrf
                @include('academic-years.partials.form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Academic Year') }}</button>
                    <a class="btn btn-link" href="{{ route('academic-years.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
