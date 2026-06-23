<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Marks Entry') }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Enter a complete eligible Student roster for one ongoing Exam Subject.') }}</p>
        </div>
    </x-slot>

    @include('examinations.partials.navigation')

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif

    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('exam-marks-entry.index') }}">
        <div class="col-md-8 col-xl-6">
            <label class="form-label" for="exam_subject_id">{{ __('Exam Subject') }} <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="exam_subject_id" name="exam_subject_id" class="form-select @error('exam_subject_id') is-invalid @enderror" required>
                <option value="">{{ __('Select Exam Subject') }}</option>
                @foreach ($examSubjects as $examSubject)
                    <option value="{{ $examSubject->id }}" @selected((int) old('exam_subject_id', request('exam_subject_id')) === (int) $examSubject->id)>
                        {{ $examSubject->exam->name }} · {{ $examSubject->schoolClass->name }} · {{ $examSubject->subject->name }}
                    </option>
                @endforeach
            </select>
            @error('exam_subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 col-xl-2">
            <button class="btn btn-outline-primary w-100" type="submit">
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>{{ __('Load Roster') }}
            </button>
        </div>
    </form>

    @if ($examSubjects->isEmpty())
        <div class="alert alert-info" role="status">{{ __('No ongoing Exam Subjects are available for marks entry.') }}</div>
    @elseif ($selectedExamSubject)
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $selectedExamSubject->exam->name }} · {{ $selectedExamSubject->schoolClass->name }} · {{ $selectedExamSubject->subject->name }}</h2>
                <p class="small text-body-secondary mb-0">
                    {{ __('Max marks: :max · Passing marks: :passing', ['max' => $selectedExamSubject->max_marks, 'passing' => $selectedExamSubject->passing_marks]) }}
                </p>
            </div>
        </div>

        @if ($roster->isEmpty())
            <div class="alert alert-info" role="status">{{ __('No eligible active Student Enrollments exist for this Exam Subject.') }}</div>
        @else
            @php($oldEntries = collect(old('entries', []))->filter(fn ($entry) => is_array($entry) && isset($entry['student_id']))->keyBy(fn ($entry) => (int) $entry['student_id']))
            <form method="POST" action="{{ route('exam-marks-entry.store') }}">
                @csrf
                <input type="hidden" name="exam_subject_id" value="{{ $selectedExamSubject->id }}">

                @error('entries')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">{{ __('Student') }}</th>
                                    <th scope="col">{{ __('Admission No.') }}</th>
                                    <th scope="col">{{ __('Marks Obtained') }}</th>
                                    <th scope="col">{{ __('Absent') }}</th>
                                    <th scope="col">{{ __('Remark') }}</th>
                                    <th scope="col">{{ __('Existing') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roster as $index => $enrollment)
                                    @php($result = $existing->get($enrollment->student_id))
                                    @php($oldEntry = $oldEntries->get((int) $enrollment->student_id, []))
                                    @php($isAbsent = array_key_exists('absent', $oldEntry) ? filter_var($oldEntry['absent'], FILTER_VALIDATE_BOOLEAN) : $result?->result_status === \App\Models\ExamResult::STATUS_ABSENT)
                                    @php($marks = array_key_exists('marks_obtained', $oldEntry) ? $oldEntry['marks_obtained'] : ($result && $result->result_status !== \App\Models\ExamResult::STATUS_ABSENT ? $result->marks_obtained : null))
                                    @php($remarks = array_key_exists('remarks', $oldEntry) ? $oldEntry['remarks'] : $result?->remarks)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ trim($enrollment->student->first_name.' '.$enrollment->student->last_name) }}</div>
                                            <input type="hidden" name="entries[{{ $index }}][student_id]" value="{{ $enrollment->student_id }}">
                                        </td>
                                        <td>{{ $enrollment->student->admission_no }}</td>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $selectedExamSubject->max_marks }}"
                                                name="entries[{{ $index }}][marks_obtained]"
                                                class="form-control form-control-sm @error("entries.$index.marks_obtained") is-invalid @enderror"
                                                value="{{ $marks }}"
                                                @disabled($isAbsent)
                                                data-marks-input
                                            >
                                            @error("entries.$index.marks_obtained")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="entries[{{ $index }}][absent]"
                                                    value="1"
                                                    id="absent-{{ $index }}"
                                                    @checked($isAbsent)
                                                    data-absent-toggle
                                                >
                                                <label class="form-check-label" for="absent-{{ $index }}">{{ __('Absent') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="entries[{{ $index }}][remarks]" class="form-control form-control-sm" value="{{ $remarks }}" maxlength="500">
                                        </td>
                                        <td>
                                            @if ($result)
                                                <span class="badge text-bg-secondary">{{ ucfirst($result->result_status) }}</span>
                                            @else
                                                <span class="text-body-secondary small">{{ __('New') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1" aria-hidden="true"></i>{{ __('Save Complete Roster') }}
                    </button>
                </div>
            </form>
        @endif
    @endif

    @push('scripts')
        <script>
            document.querySelectorAll('[data-absent-toggle]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const row = checkbox.closest('tr');
                    const marksInput = row?.querySelector('[data-marks-input]');
                    if (!marksInput) return;
                    marksInput.disabled = checkbox.checked;
                    if (checkbox.checked) marksInput.value = '';
                });
            });
        </script>
    @endpush
</x-app-layout>
