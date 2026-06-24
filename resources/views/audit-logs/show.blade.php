@php
    $formatValue = static function (mixed $value): string {
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Audit Details') }}</h1>
            <p class="text-body-secondary mb-0">{{ $auditLog->event }} · {{ class_basename($auditLog->auditable_type ?? 'record') }}</p>
        </div>
    </x-slot>

    @include('system-operations.partials.navigation')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Recorded At') }}</dt>
                <dd class="col-sm-9">{{ $auditLog->created_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>

                @if ($showSchool)
                    <dt class="col-sm-3">{{ __('School') }}</dt>
                    <dd class="col-sm-9">{{ $auditLog->school?->name ?? '—' }}</dd>
                @endif

                <dt class="col-sm-3">{{ __('User') }}</dt>
                <dd class="col-sm-9">{{ $auditLog->user?->name ?? __('System') }}</dd>

                <dt class="col-sm-3">{{ __('Event') }}</dt>
                <dd class="col-sm-9">{{ $auditLog->event }}</dd>

                <dt class="col-sm-3">{{ __('Auditable') }}</dt>
                <dd class="col-sm-9">
                    @if ($auditLog->auditable_type)
                        {{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3">{{ __('IP Address') }}</dt>
                <dd class="col-sm-9">{{ $auditLog->ip_address ?? '—' }}</dd>

                <dt class="col-sm-3">{{ __('User Agent') }}</dt>
                <dd class="col-sm-9 text-break">{{ $auditLog->user_agent ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ __('Previous Values') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">{{ __('Field') }}</th>
                                <th scope="col">{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($oldValues as $field => $value)
                                <tr>
                                    <td class="fw-semibold">{{ $field }}</td>
                                    <td class="text-break">{{ $formatValue($value) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-body-secondary py-3">{{ __('No previous values recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ __('New Values') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">{{ __('Field') }}</th>
                                <th scope="col">{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($newValues as $field => $value)
                                <tr>
                                    <td class="fw-semibold">{{ $field }}</td>
                                    <td class="text-break">{{ $formatValue($value) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-body-secondary py-3">{{ __('No new values recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <a class="btn btn-link px-0" href="{{ route('audit-logs.index') }}">{{ __('Back to audit trail') }}</a>
</x-app-layout>
