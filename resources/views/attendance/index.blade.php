<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Attendance') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Record a complete eligible Section roster for one school day.') }}</p>
        </div>
    </x-slot>

    @include('attendance.partials.navigation')

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    @if (! $currentYear)
        <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            {{ __('Set exactly one active Academic Year as current before recording Attendance.') }}
        </div>
    @endif

    @php($classes = $sections->pluck('schoolClass')->filter()->unique('id')->values())
    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('attendance.index') }}">
        <div class="col-md-4 col-xl-3">
            <label class="form-label" for="attendance_date">{{ __('Date') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="attendance_date" name="attendance_date" type="date" class="form-control @error('attendance_date') is-invalid @enderror" value="{{ old('attendance_date', $attendanceDate) }}" required>
            @error('attendance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 col-xl-3">
            <label class="form-label" for="class_id">{{ __('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                <option value="">{{ __('Select Class') }}</option>
                @foreach ($classes as $schoolClass)
                    <option value="{{ $schoolClass->id }}" @selected((int) old('class_id', $selectedClassId) === (int) $schoolClass->id)>{{ $schoolClass->name }}</option>
                @endforeach
            </select>
            @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 col-xl-4">
            <label class="form-label" for="section_id">{{ __('Section') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                <option value="">{{ __('Select Section') }}</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((int) old('section_id', $selectedSection?->id) === (int) $section->id)>{{ $section->schoolClass->name }} / {{ $section->name }}</option>
                @endforeach
            </select>
            @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-xl-2">
            <button class="btn btn-outline-primary w-100" type="submit" @disabled(! $currentYear)>
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>{{ __('Load Roster') }}
            </button>
        </div>
    </form>

    @if ($selectedSection)
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $selectedSection->schoolClass->name }} / {{ $selectedSection->name }}</h2>
                <p class="small text-body-secondary mb-0">
                    {{ $currentYear->name }} · {{ $attendanceDate }} · {{ __('Timezone: :timezone', ['timezone' => $timezone]) }}
                </p>
            </div>
            @if ($roster->isNotEmpty())
                <button class="btn btn-sm btn-outline-success align-self-start" type="button" data-mark-all-present>
                    <i class="bi bi-check2-all me-1" aria-hidden="true"></i>{{ __('Mark All Present') }}
                </button>
            @endif
        </div>

        @if ($roster->isEmpty())
            <div class="alert alert-info" role="status">{{ __('No eligible active Student Enrollments exist for this Section and date.') }}</div>
        @else
            @php($oldEntries = collect(old('entries', []))->filter(fn ($entry) => is_array($entry) && isset($entry['student_id']))->keyBy(fn ($entry) => (int) $entry['student_id']))
            <form method="POST" action="{{ route('attendance.store') }}" data-attendance-roster>
                @csrf
                <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">
                <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">

                @error('entries')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                @error('mode')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 attendance-roster-table">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">{{ __('Student') }}</th>
                                    <th scope="col">{{ __('Roll Number') }}</th>
                                    <th scope="col">{{ __('Status') }} <span class="text-danger" aria-hidden="true">*</span></th>
                                    <th scope="col">{{ __('Remark') }}</th>
                                    <th scope="col">{{ __('Record') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roster as $index => $enrollment)
                                    @php($attendance = $existingAttendances->get($enrollment->student_id))
                                    @php($oldEntry = $oldEntries->get((int) $enrollment->student_id, []))
                                    @php($selectedStatus = $oldEntry['status'] ?? $attendance?->status)
                                    @php($remarks = array_key_exists('remarks', $oldEntry) ? $oldEntry['remarks'] : $attendance?->remarks)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ trim($enrollment->student->first_name.' '.$enrollment->student->last_name) }}</div>
                                            <div class="small text-body-secondary">{{ $enrollment->student->admission_no }}</div>
                                            <input type="hidden" name="entries[{{ $index }}][student_id]" value="{{ $enrollment->student_id }}">
                                        </td>
                                        <td>{{ $enrollment->roll_no }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm attendance-status-control" role="group" aria-label="{{ __('Attendance status for :student', ['student' => $enrollment->student->first_name]) }}">
                                                @foreach ([
                                                    \App\Models\Attendance::STATUS_PRESENT => __('Present'),
                                                    \App\Models\Attendance::STATUS_ABSENT => __('Absent'),
                                                    \App\Models\Attendance::STATUS_LEAVE => __('Leave'),
                                                    \App\Models\Attendance::STATUS_LATE => __('Late'),
                                                ] as $value => $label)
                                                    <input class="btn-check" type="radio" name="entries[{{ $index }}][status]" id="status-{{ $index }}-{{ $value }}" value="{{ $value }}" @checked($selectedStatus === $value) required>
                                                    <label class="btn btn-outline-secondary" for="status-{{ $index }}-{{ $value }}">{{ $label }}</label>
                                                @endforeach
                                            </div>
                                            @error("entries.$index.status")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <label class="visually-hidden" for="remarks-{{ $index }}">{{ __('Remark for :student', ['student' => $enrollment->student->first_name]) }}</label>
                                            <input id="remarks-{{ $index }}" name="entries[{{ $index }}][remarks]" class="form-control form-control-sm @error("entries.$index.remarks") is-invalid @enderror" value="{{ $remarks }}" maxlength="500" placeholder="{{ __('Optional') }}">
                                            @error("entries.$index.remarks")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <span class="badge {{ $attendance ? 'text-bg-warning' : 'text-bg-primary' }}">{{ $attendance ? __('Correction') : __('New') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary" type="submit" name="mode" value="roster">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('Save Complete Roster') }}
                    </button>
                    @if ($canMarkHoliday)
                        <button class="btn btn-outline-secondary" type="submit" name="mode" value="holiday" formnovalidate onclick="return confirm('{{ __('Mark the complete eligible roster as Holiday?') }}')">
                            <i class="bi bi-calendar2-x me-1" aria-hidden="true"></i>{{ __('Mark Section Holiday') }}
                        </button>
                    @endif
                </div>
            </form>
        @endif
    @endif
</x-app-layout>
