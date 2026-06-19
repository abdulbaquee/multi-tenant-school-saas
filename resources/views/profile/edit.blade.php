<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Profile') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Manage your account information and password.') }}</p>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="col-lg-6">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
