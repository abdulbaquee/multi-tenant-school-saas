<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Attendance History') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Search retained operational Attendance records within your authorized scope.') }}</p>
        </div>
    </x-slot>

    @include('attendance.partials.navigation')

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('attendance.history') }}">
        <div class="col-md-4 col-xl-3">
            <label class="form-label" for="section_id">{{ __('Section') }}</label>
            <select id="section_id" name="section_id" class="form-select">
                <option value="">{{ __('All authorized Sections') }}</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((int) request('section_id') === (int) $section->id)>{{ $section->schoolClass?->name }} / {{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-xl-2">
            <label class="form-label" for="date_from">{{ __('From') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-4 col-xl-2">
            <label class="form-label" for="date_to">{{ __('To') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-4 col-xl-2">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select id="status" name="status" class="form-select">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['present', 'absent', 'leave', 'late', 'holiday'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-xl-2">
            <label class="form-label" for="search">{{ __('Student') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Name or admission number') }}">
        </div>
        <div class="col-md-2 col-xl-1 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('attendance.history') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('Date') }}</th>
                        <th scope="col">{{ __('Student') }}</th>
                        <th scope="col">{{ __('Placement') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Original Marker') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->attendance_date->format('Y-m-d') }}</td>
                            <td>
                                <div class="fw-semibold">{{ trim($attendance->student->first_name.' '.$attendance->student->last_name) }}</div>
                                <div class="small text-body-secondary">{{ $attendance->student->admission_no }}</div>
                            </td>
                            <td>{{ $attendance->schoolClass->name }} / {{ $attendance->section->name }}</td>
                            <td><span class="badge attendance-status-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span></td>
                            <td>{{ $attendance->markedBy->name }}</td>
                            <td class="text-end">
                                @can('update', $attendance)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.edit', $attendance) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Correct') }}</a>
                                @else
                                    <span class="small text-body-secondary">{{ __('Read only') }}</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No Attendance records match these filters.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $attendances->links() }}</div>
</x-app-layout>
