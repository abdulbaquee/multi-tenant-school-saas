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
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('reports.examinations.export', request()->query()) }}">
                        <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('reports.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('reports.examinations.index') }}">
        <div class="col-md-3">
            <label class="form-label" for="search">{{ $mode === 'platform' ? __('School') : __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ $mode === 'platform' ? __('School name or code') : __('Student, exam, or subject') }}">
        </div>
        @if ($mode === 'school')
            <div class="col-md-2">
                <label class="form-label" for="status">{{ __('Result status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['pending', 'pass', 'fail', 'absent'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-2">
            <label class="form-label" for="date_from">{{ __('Exam from') }}</label>
            <input id="date_from" name="date_from" type="date" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">{{ __('Exam to') }}</label>
            <input id="date_to" name="date_to" type="date" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.examinations.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
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
                            <th scope="col">{{ __('Results') }}</th>
                            <th scope="col">{{ __('Pass') }}</th>
                            <th scope="col">{{ __('Fail') }}</th>
                            <th scope="col">{{ __('Absent') }}</th>
                        </tr>
                    @else
                        <tr>
                            <th scope="col">{{ __('Exam') }}</th>
                            <th scope="col">{{ __('Subject') }}</th>
                            <th scope="col">{{ __('Admission No') }}</th>
                            <th scope="col">{{ __('Student Name') }}</th>
                            <th scope="col">{{ __('Marks') }}</th>
                            <th scope="col">{{ __('Grade') }}</th>
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
                                <td>{{ $school->total_results_count }}</td>
                                <td>{{ $school->pass_results_count }}</td>
                                <td>{{ $school->fail_results_count }}</td>
                                <td>{{ $school->absent_results_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No schools match these filters.') }}</td></tr>
                        @endforelse
                    @else
                        @forelse ($results as $result)
                            <tr>
                                <td class="fw-semibold">{{ $result->exam->name }}</td>
                                <td>{{ $result->subject->name }}</td>
                                <td>{{ $result->student->admission_no }}</td>
                                <td>{{ trim($result->student->first_name.' '.$result->student->last_name) }}</td>
                                <td>{{ $result->marks_obtained !== null ? number_format((float) $result->marks_obtained, 2) : '—' }}</td>
                                <td>{{ $result->gradeScale?->grade ?? '—' }}</td>
                                <td>{{ ucfirst($result->result_status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No examination results match these filters.') }}</td></tr>
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
            {{ $results->links() }}
        @endif
    </div>
</x-app-layout>
