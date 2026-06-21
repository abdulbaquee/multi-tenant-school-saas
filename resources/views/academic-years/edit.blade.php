<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Academic Years') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('academic-years.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Edit Academic Year') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Update the year name and date range without changing lifecycle state.') }}</p>
        </div>
    </x-slot>

    @include('academic.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('academic-years.update', $academicYear) }}">
                @csrf
                @method('put')
                @include('academic-years.partials.form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Academic Year') }}</button>
                    <a class="btn btn-link" href="{{ route('academic-years.show', $academicYear) }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
