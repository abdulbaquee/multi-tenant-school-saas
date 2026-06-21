<div class="row g-3">
    <div class="col-12"><p class="small text-body-secondary mb-0"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p></div>
    <div class="col-12">
        <label for="academic_year_id" class="form-label">{{ __('Academic Year') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <select id="academic_year_id" name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
            <option value="">{{ __('Select an active academic year') }}</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected((string) old('academic_year_id', $academicTerm->academic_year_id ?? request('academic_year_id')) === (string) $year->id)>{{ $year->name }} ({{ $year->start_date->format('d M Y') }} - {{ $year->end_date->format('d M Y') }})</option>
            @endforeach
        </select>
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label for="name" class="form-label">{{ __('Term Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $academicTerm->name ?? '') }}" maxlength="80" placeholder="Term 1" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="term_order" class="form-label">{{ __('Term Order') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="term_order" name="term_order" type="number" class="form-control @error('term_order') is-invalid @enderror" value="{{ old('term_order', $academicTerm->term_order ?? 1) }}" min="1" max="255" required>
        @error('term_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="start_date" class="form-label">{{ __('Start Date') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="start_date" name="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', isset($academicTerm) ? $academicTerm->start_date->toDateString() : '') }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="end_date" class="form-label">{{ __('End Date') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="end_date" name="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', isset($academicTerm) ? $academicTerm->end_date->toDateString() : '') }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
