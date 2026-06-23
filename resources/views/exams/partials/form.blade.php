<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="academic_year_id">{{ __('Academic Year') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="academic_year_id" name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required @if(isset($exam)) disabled @endif>
            @if(isset($exam))
                <option value="{{ $exam->academic_year_id }}" selected>{{ $exam->academicYear->name }}</option>
            @else
                <option value="">{{ __('Select academic year') }}</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected((int) old('academic_year_id') === $year->id)>{{ $year->name }}</option>
                @endforeach
            @endif
        </select>
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="academic_term_id">{{ __('Academic Term') }}</label>
        <select id="academic_term_id" name="academic_term_id" class="form-select @error('academic_term_id') is-invalid @enderror" @if(isset($exam)) disabled @endif>
            <option value="">{{ __('Optional term') }}</option>
            @foreach (($terms ?? collect()) as $term)
                <option value="{{ $term->id }}" @selected((int) old('academic_term_id', $exam->academic_term_id ?? 0) === $term->id)>{{ $term->name }}</option>
            @endforeach
        </select>
        @error('academic_term_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">{{ __('Exam Name') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $exam->name ?? '') }}" maxlength="120" required @if(isset($exam)) readonly @endif>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="exam_type">{{ __('Exam Type') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="exam_type" name="exam_type" class="form-select @error('exam_type') is-invalid @enderror" required>
            @foreach ([\App\Models\Exam::TYPE_TERM => __('Term'), \App\Models\Exam::TYPE_UNIT_TEST => __('Unit Test'), \App\Models\Exam::TYPE_FINAL => __('Final'), \App\Models\Exam::TYPE_OTHER => __('Other')] as $value => $label)
                <option value="{{ $value }}" @selected(old('exam_type', $exam->exam_type ?? \App\Models\Exam::TYPE_TERM) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('exam_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="start_date">{{ __('Start Date') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', isset($exam) ? $exam->start_date?->toDateString() : '') }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="end_date">{{ __('End Date') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', isset($exam) ? $exam->end_date?->toDateString() : '') }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
