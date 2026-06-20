<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Schools') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('schools.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('School Details') }}</x-slot>

    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 mb-0">{{ $school->name }}</h1>
                    <span class="badge {{ $school->status === \App\Models\School::STATUS_ACTIVE ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ ucfirst($school->status) }}
                    </span>
                </div>
                <p class="text-body-secondary mb-0">{{ $school->code }} · {{ trans_choice(':count user|:count users', $school->users_count, ['count' => $school->users_count]) }}</p>
            </div>

            @can('update', $school)
                <a class="btn btn-outline-primary" href="{{ route('schools.edit', $school) }}">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit School') }}
                </a>
            @endcan
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('School Information') }}</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Official Email') }}</dt>
                        <dd class="col-sm-8">{{ $school->email }}</dd>

                        <dt class="col-sm-4">{{ __('Official Phone') }}</dt>
                        <dd class="col-sm-8">{{ $school->phone ?? '—' }}</dd>

                        <dt class="col-sm-4">{{ __('Principal') }}</dt>
                        <dd class="col-sm-8">{{ $school->principal_name ?? '—' }}</dd>

                        <dt class="col-sm-4">{{ __('Address') }}</dt>
                        <dd class="col-sm-8">
                            {{ collect([$school->address, $school->city, $school->state, $school->postal_code, $school->country])->filter()->join(', ') ?: '—' }}
                        </dd>

                        <dt class="col-sm-4">{{ __('Website') }}</dt>
                        <dd class="col-sm-8">
                            @if ($school->website)
                                <a href="{{ $school->website }}" target="_blank" rel="noopener noreferrer">{{ $school->website }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">{{ __('Default Settings') }}</h2>
                    <dl class="mb-0">
                        <dt>{{ __('Timezone') }}</dt>
                        <dd>{{ $school->settings?->timezone ?? '—' }}</dd>

                        <dt>{{ __('Currency') }}</dt>
                        <dd>{{ $school->settings?->currency ?? '—' }}</dd>

                        <dt>{{ __('Academic Year Starts') }}</dt>
                        <dd>{{ $school->settings ? \Carbon\Carbon::create()->month($school->settings->academic_year_start_month)->format('F') : '—' }}</dd>

                        <dt>{{ __('Grading System') }}</dt>
                        <dd class="mb-0">{{ $school->settings ? ucfirst($school->settings->grading_system) : '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        @can('deactivate', $school)
            <div class="card border-danger-subtle shadow-sm">
                <div class="card-body">
                    <h2 class="h5">{{ __('Deactivate School') }}</h2>
                    <p class="text-body-secondary">{{ __('Users will be signed out and unable to log in. School data and history will be preserved.') }}</p>

                    <form method="POST" action="{{ route('schools.deactivate', $school) }}">
                        @csrf
                        @method('patch')
                        <label for="reason" class="form-label">
                            {{ __('Reason') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                        </label>
                        <textarea id="reason" name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" maxlength="2000" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <button class="btn btn-outline-danger mt-3" type="submit" onclick="return confirm('{{ __('Deactivate this school and sign out all of its users?') }}')">
                            <i class="bi bi-slash-circle me-1" aria-hidden="true"></i>{{ __('Deactivate School') }}
                        </button>
                    </form>
                </div>
            </div>
        @endcan

        @can('activate', $school)
            <div class="card border-success-subtle shadow-sm">
                <div class="card-body">
                    <h2 class="h5">{{ __('Reactivate School') }}</h2>
                    <p class="text-body-secondary">{{ __('Active school users will be able to sign in again. Individually inactive users remain inactive.') }}</p>
                    @if ($school->deactivation_reason)
                        <p><strong>{{ __('Deactivation reason:') }}</strong> {{ $school->deactivation_reason }}</p>
                    @endif
                    <form method="POST" action="{{ route('schools.activate', $school) }}">
                        @csrf
                        @method('patch')
                        <button class="btn btn-outline-success" type="submit">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Activate School') }}
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
