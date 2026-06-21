<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Students') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('students.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Create Enrollment') }}</x-slot>
    <x-slot name="header">
        <h1 class="h3 mb-1">{{ __('Create Enrollment') }}</h1>
        <p class="text-body-secondary mb-0">{{ __('Assign :student to the current Academic Year, Class, and Section.', ['student' => trim($student->first_name.' '.$student->last_name)]) }}</p>
    </x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <div>
                    <div class="fw-semibold">{{ trim($student->first_name.' '.$student->last_name) }}</div>
                    <div class="small text-body-secondary">{{ __('Admission Number: :number', ['number' => $student->admission_no]) }}</div>
                </div>
                <span class="badge text-bg-success">{{ __('Active Student') }}</span>
            </div>

            @if (! $academicYear)
                <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
                    <span>{{ __('Set one active Academic Year as current before creating an Enrollment.') }}</span>
                    <a class="btn btn-sm btn-outline-warning" href="{{ route('academic-years.index') }}">{{ __('Manage Academic Years') }}</a>
                </div>
            @elseif ($hasActiveEnrollment)
                <div class="alert alert-warning" role="alert">{{ __('Complete the Student’s active Enrollment before creating another placement.') }}</div>
            @elseif ($alreadyEnrolledCurrentYear)
                <div class="alert alert-warning" role="alert">{{ __('This Student already has a retained Enrollment for the current Academic Year.') }}</div>
            @elseif ($classes->isEmpty())
                <div class="alert alert-warning" role="alert">{{ __('Create an active Class with an active Section in the current Academic Year before enrolling this Student.') }}</div>
            @endif

            @if ($academicYear)
                <form method="POST" action="{{ route('student-enrollments.store', $student) }}">
                    @csrf
                    <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">
                    <p class="small text-body-secondary mb-3"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="academic_year" class="form-label">{{ __('Academic Year') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="academic_year" class="form-control" value="{{ $academicYear->name }}" disabled>
                            <div class="form-text">{{ $academicYear->start_date->format('d M Y') }} – {{ $academicYear->end_date->format('d M Y') }}</div>
                            @error('academic_year_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="enrollment_date" class="form-label">{{ __('Enrollment Date') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="enrollment_date" name="enrollment_date" type="date" class="form-control @error('enrollment_date') is-invalid @enderror" value="{{ old('enrollment_date', max($student->admission_date->toDateString(), $academicYear->start_date->toDateString())) }}" min="{{ max($student->admission_date->toDateString(), $academicYear->start_date->toDateString()) }}" max="{{ min(now()->toDateString(), $academicYear->end_date->toDateString()) }}" required>
                            @error('enrollment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="class_id" class="form-label">{{ __('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select a class') }}</option>
                                @foreach ($classes as $schoolClass)
                                    <option value="{{ $schoolClass->id }}" @selected((int) old('class_id') === $schoolClass->id)>{{ $schoolClass->name }}</option>
                                @endforeach
                            </select>
                            @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="section_id" class="form-label">{{ __('Section') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select a section') }}</option>
                                @foreach ($classes as $schoolClass)
                                    <optgroup label="{{ $schoolClass->name }}">
                                        @foreach ($schoolClass->sections as $section)
                                            <option value="{{ $section->id }}" @selected((int) old('section_id') === $section->id)>{{ $section->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Select a Section from the chosen Class.') }}</div>
                            @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="roll_no" class="form-label">{{ __('Roll Number') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="roll_no" name="roll_no" type="text" class="form-control @error('roll_no') is-invalid @enderror" value="{{ old('roll_no') }}" maxlength="50" required>
                            <div class="form-text">{{ __('The roll number and placement cannot be changed after saving.') }}</div>
                            @error('roll_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-primary" type="submit" @disabled($hasActiveEnrollment || $alreadyEnrolledCurrentYear || $classes->isEmpty())><i class="bi bi-person-check me-1" aria-hidden="true"></i>{{ __('Create Enrollment') }}</button>
                        <a class="btn btn-outline-secondary" href="{{ route('students.show', $student) }}">{{ __('Cancel') }}</a>
                    </div>
                </form>
            @else
                <a class="btn btn-outline-secondary" href="{{ route('students.show', $student) }}">{{ __('Back to Student') }}</a>
            @endif
        </div>
    </div>
</x-app-layout>
