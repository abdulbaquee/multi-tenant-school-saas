<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Exam Subjects') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('exam-subjects.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Assign') }}</x-slot>
    <x-slot name="header">
        <div><h1 class="h3 mb-1">{{ __('Assign Exam Subject') }}</h1><p class="text-body-secondary mb-0">{{ __('Assign an active Subject and Class to a scheduled or ongoing Exam.') }}</p></div>
    </x-slot>

    @include('examinations.partials.navigation')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('exam-subjects.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="exam_id">{{ __('Exam') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="exam_id" name="exam_id" class="form-select @error('exam_id') is-invalid @enderror" required onchange="window.location='{{ route('exam-subjects.create') }}?exam_id=' + this.value + '&class_id=' + (document.getElementById('class_id').value || '')">
                            <option value="">{{ __('Select an exam') }}</option>
                            @foreach ($exams as $examOption)
                                <option value="{{ $examOption->id }}" @selected((int) old('exam_id', request('exam_id')) === $examOption->id)>{{ $examOption->name }} · {{ ucfirst($examOption->status) }}</option>
                            @endforeach
                        </select>
                        @error('exam_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="class_id">{{ __('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required onchange="window.location='{{ route('exam-subjects.create') }}?exam_id=' + (document.getElementById('exam_id').value || '') + '&class_id=' + this.value">
                            <option value="">{{ __('Select a class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) old('class_id', request('class_id')) === $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="subject_id">{{ __('Subject') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <select id="subject_id" name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required @disabled($subjects->isEmpty())>
                            <option value="">{{ __('Select a subject') }}</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((int) old('subject_id') === $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="exam_date">{{ __('Exam Date') }}</label>
                        <input id="exam_date" name="exam_date" type="date" class="form-control @error('exam_date') is-invalid @enderror" value="{{ old('exam_date') }}">
                        @error('exam_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="max_marks">{{ __('Maximum Marks') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="max_marks" name="max_marks" type="number" step="0.01" min="0.01" class="form-control @error('max_marks') is-invalid @enderror" value="{{ old('max_marks', '100.00') }}" required>
                        @error('max_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="passing_marks">{{ __('Passing Marks') }} <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="passing_marks" name="passing_marks" type="number" step="0.01" min="0" class="form-control @error('passing_marks') is-invalid @enderror" value="{{ old('passing_marks', '33.00') }}" required>
                        @error('passing_marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('Maximum and passing marks are validated on the server.') }}</div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit" @disabled($exams->isEmpty())><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ __('Assign Exam Subject') }}</button>
                    <a class="btn btn-link" href="{{ route('exam-subjects.index') }}">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
