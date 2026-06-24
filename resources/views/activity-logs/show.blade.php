<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Activity Details') }}</h1>
            <p class="text-body-secondary mb-0">{{ $activityLog->module }} · {{ $activityLog->action }}</p>
        </div>
    </x-slot>

    @include('system-operations.partials.navigation')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Recorded At') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->created_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>

                @if ($showSchool)
                    <dt class="col-sm-3">{{ __('School') }}</dt>
                    <dd class="col-sm-9">{{ $activityLog->school?->name ?? '—' }}</dd>
                @endif

                <dt class="col-sm-3">{{ __('User') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->user?->name ?? __('System') }}</dd>

                <dt class="col-sm-3">{{ __('Module') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->module }}</dd>

                <dt class="col-sm-3">{{ __('Action') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->action }}</dd>

                <dt class="col-sm-3">{{ __('Description') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->description ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('Subject') }}</dt>
                <dd class="col-sm-9">
                    @if ($activityLog->subject_type)
                        {{ class_basename($activityLog->subject_type) }} #{{ $activityLog->subject_id }}
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3">{{ __('IP Address') }}</dt>
                <dd class="col-sm-9">{{ $activityLog->ip_address ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('User Agent') }}</dt>
                <dd class="col-sm-9 text-break">{{ $activityLog->user_agent ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <a class="btn btn-link px-0" href="{{ route('activity-logs.index') }}">{{ __('Back to activity logs') }}</a>
</x-app-layout>
