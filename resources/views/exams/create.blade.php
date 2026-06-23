<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Exams') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('exams.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Create Exam') }}</h1><p class="text-body-secondary mb-0">{{ __('Add a tenant-owned exam for the current Academic Year.') }}</p></div>
    </x-slot>

    @include('examinations.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('exams.store') }}">
                @csrf
                @include('exams.partials.form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Exam') }}</button>
                    <a class="btn btn-link" href="{{ route('exams.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
