<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Academic Terms') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('academic-terms.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Create Academic Term') }}</h1><p class="text-body-secondary mb-0">{{ __('Add an ordered, non-overlapping term inside an active academic year.') }}</p></div></x-slot>
    @include('academic.partials.navigation')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($academicYears->isEmpty())
                <div class="alert alert-warning mb-0">{{ __('Create or reactivate an academic year before adding terms.') }}</div>
            @else
                <form method="POST" action="{{ route('academic-terms.store') }}">
                    @csrf
                    @include('academic-terms.partials.form')
                    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Academic Term') }}</button><a class="btn btn-link" href="{{ route('academic-terms.index') }}">{{ __('Cancel') }}</a></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
