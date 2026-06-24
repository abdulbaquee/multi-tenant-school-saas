<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h1 class="h3 mb-1">{{ $title }}</h1>
                <p class="text-body-secondary mb-0">{{ $description }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i>{{ __('Print') }}
                </button>
                @if ($canExport)
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('reports.attendance.export', request()->query()) }}">
                        <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('reports.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('reports.attendance.index') }}">
        @if ($mode === 'school' && $sections->isNotEmpty())
            <div class="col-md-3">
                <label class="form-label" for="section_id">{{ __('Section') }}</label>
                <select id="section_id" name="section_id" class="form-select">
                    <option value="">{{ __('All authorized sections') }}</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((int) request('section_id') === (int) $section->id)>{{ $section->schoolClass?->name }} / {{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('From') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('To') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        @if ($mode === 'school')
            <div class="col-md-2">
                <label class="form-label" for="status">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['present', 'absent', 'leave', 'late', 'holiday'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="search">{{ __('Student') }}</label>
                <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Name or admission number') }}">
            </div>
        @else
            <div class="col-md-4">
                <label class="form-label" for="search">{{ __('School') }}</label>
                <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('School name or code') }}">
            </div>
        @endif
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.attendance.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
        </div>
    </form>

    @include('reports.partials.summary-cards')

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    @if ($mode === 'platform')
                        <tr>
                            <th scope="col">{{ __('School Code') }}</th>
                            <th scope="col">{{ __('School Name') }}</th>
                            <th scope="col">{{ __('Records') }}</th>
                            <th scope="col">{{ __('Present') }}</th>
                            <th scope="col">{{ __('Absent') }}</th>
                            <th scope="col">{{ __('Leave') }}</th>
                            <th scope="col">{{ __('Late') }}</th>
                        </tr>
                    @else
                        <tr>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col">{{ __('Student') }}</th>
                            <th scope="col">{{ __('Placement') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if ($mode === 'platform')
                        @forelse ($schools as $school)
                            <tr>
                                <td class="fw-semibold">{{ $school->code }}</td>
                                <td>{{ $school->name }}</td>
                                <td>{{ $school->total_records }}</td>
                                <td>{{ $school->present_records }}</td>
                                <td>{{ $school->absent_records }}</td>
                                <td>{{ $school->leave_records }}</td>
                                <td>{{ $school->late_records }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No schools match these filters.') }}</td></tr>
                        @endforelse
                    @else
                        @forelse ($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->attendance_date->format('Y-m-d') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ trim($attendance->student->first_name.' '.$attendance->student->last_name) }}</div>
                                    <div class="small text-body-secondary">{{ $attendance->student->admission_no }}</div>
                                </td>
                                <td>{{ $attendance->schoolClass->name }} / {{ $attendance->section->name }}</td>
                                <td><span class="badge attendance-status-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-body-secondary py-4">{{ __('No attendance records match these filters.') }}</td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        @if ($mode === 'platform')
            {{ $schools->links() }}
        @else
            {{ $attendances->links() }}
        @endif
    </div>
</x-app-layout>
