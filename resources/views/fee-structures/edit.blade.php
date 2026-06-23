<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Structures') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-structures.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Edit Fee Structure') }}</h1><p class="text-body-secondary mb-0">{{ __('Update amount, due date, or frequency without changing scope identity.') }}</p></div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}">
                @csrf
                @method('put')
                @include('fee-structures.partials.form', ['feeStructure' => $feeStructure, 'categories' => collect(), 'academicYears' => collect(), 'classes' => collect()])
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Fee Structure') }}</button>
                    <a class="btn btn-link" href="{{ route('fee-structures.show', $feeStructure) }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
