<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Structures') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-structures.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Create Fee Structure') }}</h1><p class="text-body-secondary mb-0">{{ __('Define a scoped Fee Structure for the current Academic Year and Class.') }}</p></div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fee-structures.store') }}">
                @csrf
                @include('fee-structures.partials.form', ['categories' => $categories, 'academicYears' => $academicYears, 'classes' => $classes])
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Fee Structure') }}</button>
                    <a class="btn btn-link" href="{{ route('fee-structures.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
