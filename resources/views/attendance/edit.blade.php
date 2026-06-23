<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Correct Attendance') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Update status or the optional operational remark while preserving the original record identity.') }}</p>
        </div>
    </x-slot>

    @include('attendance.partials.navigation')

    <div class="mb-3 small text-body-secondary">
        <a href="{{ route('attendance.history') }}">{{ __('Attendance History') }}</a>
        <span class="mx-1">/</span>{{ __('Correction') }}
    </div>

    <form method="POST" action="{{ route('attendance.update', $attendance) }}" class="card border-0 shadow-sm p-3 p-lg-4">
        @csrf
        @method('PATCH')

        <dl class="row mb-4">
            <dt class="col-sm-3">{{ __('Student') }}</dt>
            <dd class="col-sm-9">{{ trim($attendance->student->first_name.' '.$attendance->student->last_name) }} ({{ $attendance->student->admission_no }})</dd>
            <dt class="col-sm-3">{{ __('Placement') }}</dt>
            <dd class="col-sm-9">{{ $attendance->schoolClass->name }} / {{ $attendance->section->name }}</dd>
            <dt class="col-sm-3">{{ __('Date') }}</dt>
            <dd class="col-sm-9">{{ $attendance->attendance_date->format('Y-m-d') }}</dd>
            <dt class="col-sm-3">{{ __('Original Marker') }}</dt>
            <dd class="col-sm-9">{{ $attendance->markedBy->name }}</dd>
        </dl>

        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="status">{{ __('Status') }} <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ([
                        \App\Models\Attendance::STATUS_PRESENT => __('Present'),
                        \App\Models\Attendance::STATUS_ABSENT => __('Absent'),
                        \App\Models\Attendance::STATUS_LEAVE => __('Leave'),
                        \App\Models\Attendance::STATUS_LATE => __('Late'),
                        \App\Models\Attendance::STATUS_HOLIDAY => __('Holiday'),
                    ] as $value => $label)
                        @if ($attendance->status === \App\Models\Attendance::STATUS_HOLIDAY
                            ? $value === \App\Models\Attendance::STATUS_HOLIDAY
                            : $value !== \App\Models\Attendance::STATUS_HOLIDAY)
                            <option value="{{ $value }}" @selected(old('status', $attendance->status) === $value)>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-7">
                <label class="form-label" for="remarks">{{ __('Remark') }}</label>
                <input id="remarks" name="remarks" class="form-control @error('remarks') is-invalid @enderror" value="{{ old('remarks', $attendance->remarks) }}" maxlength="500">
                <div class="form-text">{{ __('Do not record diagnoses, disability details, medication, or unnecessary family information.') }}</div>
                @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>{{ __('Save Correction') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('attendance.history') }}">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>
