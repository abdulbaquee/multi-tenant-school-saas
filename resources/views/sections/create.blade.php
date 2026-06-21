<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Sections') }}</x-slot><x-slot name="breadcrumbParentUrl">{{ route('sections.index') }}</x-slot><x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Create Section') }}</h1><p class="text-body-secondary mb-0">{{ __('Add a Section under an active Class and optionally assign its Class Teacher.') }}</p></div></x-slot>
    @include('academic.partials.navigation')
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('sections.store') }}">@csrf @include('sections.partials.form')<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Section') }}</button><a class="btn btn-link" href="{{ route('sections.index') }}">{{ __('Cancel') }}</a></div></form></div></div>
</x-app-layout>
