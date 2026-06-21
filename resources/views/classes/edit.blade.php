<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Classes') }}</x-slot><x-slot name="breadcrumbParentUrl">{{ route('classes.index') }}</x-slot><x-slot name="breadcrumb">{{ __('Edit') }}</x-slot>
    <x-slot name="header"><div><h1 class="h3 mb-1">{{ __('Edit Class') }}</h1><p class="text-body-secondary mb-0">{{ __('Update Class identity and ordering without changing lifecycle state.') }}</p></div></x-slot>
    @include('academic.partials.navigation')
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('classes.update', $schoolClass) }}">@csrf @method('put') @include('classes.partials.form')<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Update Class') }}</button><a class="btn btn-link" href="{{ route('classes.show', $schoolClass) }}">{{ __('Cancel') }}</a></div></form></div></div>
</x-app-layout>
