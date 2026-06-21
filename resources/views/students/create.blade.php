<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Students') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('students.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Register') }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Register Student') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Create a private Student profile. Enrollment is completed separately.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data">
                @csrf
                @include('students.partials.form')
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Register Student') }}</button>
                    <a class="btn btn-link" href="{{ route('students.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
