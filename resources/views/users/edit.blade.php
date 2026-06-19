<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Edit User') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Update user identity, role, and access state.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('put')
                @include('users.partials.form')
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">{{ __('Update User') }}</button>
                    <a class="btn btn-link" href="{{ route('users.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
