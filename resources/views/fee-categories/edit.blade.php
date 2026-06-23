<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Fee Categories') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('fee-categories.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Edit Fee Category') }}</h1><p class="text-body-secondary mb-0">{{ __('Update the category name or description without changing lifecycle state.') }}</p></div>
    </x-slot>

    @include('fees.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('fee-categories.update', $feeCategory) }}">
                @csrf
                @method('put')
                @include('fee-categories.partials.form', ['feeCategory' => $feeCategory])
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Fee Category') }}</button>
                    <a class="btn btn-link" href="{{ route('fee-categories.show', $feeCategory) }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
