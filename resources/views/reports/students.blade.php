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
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('reports.students.export', request()->query()) }}">
                        <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Export CSV') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('reports.partials.navigation')

    <form class="row g-2 align-items-end mb-4" method="GET" action="{{ route('reports.students.index') }}">
        <div class="col-md-4">
            <label class="form-label" for="search">{{ __('Search') }}</label>
            <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" maxlength="150" placeholder="{{ __('Admission number or student name') }}">
        </div>
        @if ($mode === 'school' && ! auth()->user()->hasRoleCode(\App\Models\Role::TEACHER))
            <div class="col-md-3">
                <label class="form-label" for="state">{{ __('Status') }}</label>
                <select id="state" name="state" class="form-select">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['active' => __('Active'), 'inactive' => __('Inactive'), 'transferred' => __('Transferred'), 'graduated' => __('Graduated'), 'archived' => __('Archived')] as $value => $label)
                        <option value="{{ $value }}" @selected(request('state') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
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
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit" title="{{ __('Apply filters') }}"><i class="bi bi-funnel" aria-hidden="true"></i><span class="visually-hidden">{{ __('Apply filters') }}</span></button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.students.index') }}" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">{{ __('Clear filters') }}</span></a>
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
                            <th scope="col">{{ __('Active Students') }}</th>
                            <th scope="col">{{ __('Enrolled Students') }}</th>
                        </tr>
                    @else
                        <tr>
                            <th scope="col">{{ __('Admission No') }}</th>
                            <th scope="col">{{ __('Student Name') }}</th>
                            <th scope="col">{{ __('Class') }}</th>
                            <th scope="col">{{ __('Section') }}</th>
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
                                <td>{{ $school->active_students_count }}</td>
                                <td>{{ $school->enrolled_students_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-body-secondary py-4">{{ __('No schools match these filters.') }}</td></tr>
                        @endforelse
                    @else
                        @forelse ($students as $student)
                            @php($enrollment = $student->enrollments->first())
                            <tr>
                                <td class="fw-semibold">{{ $student->admission_no }}</td>
                                <td>{{ trim($student->first_name.' '.$student->last_name) }}</td>
                                <td>{{ $enrollment?->schoolClass?->name ?? '—' }}</td>
                                <td>{{ $enrollment?->section?->name ?? '—' }}</td>
                                <td>{{ ucfirst($student->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">{{ __('No students match these filters.') }}</td></tr>
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
            {{ $students->links() }}
        @endif
    </div>
</x-app-layout>
