<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Monthly Attendance Summary') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Review on-screen operational status totals for one authorized Section.') }}</p>
        </div>
    </x-slot>

    @include('attendance.partials.navigation')

    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('attendance.monthly-summary') }}">
        <div class="col-md-5">
            <label class="form-label" for="section_id">{{ __('Section') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                <option value="">{{ __('Select Section') }}</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((int) request('section_id') === (int) $section->id)>{{ $section->schoolClass->name }} / {{ $section->name }}</option>
                @endforeach
            </select>
            @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="month">{{ __('Month') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="month" name="month" type="month" class="form-control @error('month') is-invalid @enderror" value="{{ $month }}" required>
            @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>{{ __('Load Summary') }}</button>
        </div>
    </form>

    @if ($selectedSection)
        <div class="mb-3">
            <h2 class="h5 mb-1">{{ $selectedSection->schoolClass->name }} / {{ $selectedSection->name }}</h2>
            <p class="text-body-secondary mb-0">{{ $month }} · {{ trans_choice(':count recorded day|:count recorded days', $attendanceDays, ['count' => $attendanceDays]) }}</p>
        </div>

        <div class="row g-3">
            @foreach ([
                'present' => ['label' => __('Present'), 'icon' => 'bi-check-circle', 'tone' => 'success'],
                'absent' => ['label' => __('Absent'), 'icon' => 'bi-x-circle', 'tone' => 'danger'],
                'leave' => ['label' => __('Leave'), 'icon' => 'bi-calendar-minus', 'tone' => 'warning'],
                'late' => ['label' => __('Late'), 'icon' => 'bi-clock', 'tone' => 'primary'],
                'holiday' => ['label' => __('Holiday'), 'icon' => 'bi-calendar2-x', 'tone' => 'secondary'],
            ] as $status => $meta)
                <div class="col-sm-6 col-xl">
                    <div class="card metric-card h-100 p-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="metric-icon rounded-2 bg-{{ $meta['tone'] }}-subtle text-{{ $meta['tone'] }} d-inline-flex align-items-center justify-content-center"><i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <div class="small text-body-secondary">{{ $meta['label'] }}</div>
                                <div class="h4 mb-0">{{ $counts[$status] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info" role="status">{{ __('Select a Section and month to view operational totals.') }}</div>
    @endif
</x-app-layout>
