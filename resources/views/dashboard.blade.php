<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Dashboard') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Authentication foundation is ready.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h2>
            <p class="mb-0 text-body-secondary">
                {{ __('You are signed in to the school administration platform.') }}
            </p>
        </div>
    </div>
</x-app-layout>
