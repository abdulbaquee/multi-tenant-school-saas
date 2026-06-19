<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ $user->name }}</h1>
            <p class="text-body-secondary mb-0">{{ __('User profile and access summary.') }}</p>
        </div>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3">{{ __('Role') }}</dt>
                <dd class="col-sm-9">{{ $user->role?->name ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('School') }}</dt>
                <dd class="col-sm-9">{{ $user->school?->name ?? 'Platform' }}</dd>

                <dt class="col-sm-3">{{ __('Phone') }}</dt>
                <dd class="col-sm-9">{{ $user->phone ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('Status') }}</dt>
                <dd class="col-sm-9">{{ ucfirst($user->status) }}</dd>
            </dl>
        </div>
    </div>
</x-app-layout>
