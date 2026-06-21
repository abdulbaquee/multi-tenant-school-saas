<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Subjects') }}</x-slot><x-slot name="breadcrumbParentUrl">{{ route('subjects.index') }}</x-slot><x-slot name="breadcrumb">{{ __('Create') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Create Subject') }}</h1><p class="text-body-secondary mb-0">{{ __('Add a Subject under an active Class and optionally assign its Teacher.') }}</p></div></x-slot>
    @include('academic.partials.navigation')
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('subjects.store') }}">@csrf @include('subjects.partials.form')<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Save Subject') }}</button><a class="btn btn-link" href="{{ route('subjects.index') }}">{{ __('Cancel') }}</a></div></form></div></div>
</x-app-layout>
