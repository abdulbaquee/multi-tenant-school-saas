<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="fee_category_id">{{ __('Fee Category') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="fee_category_id" name="fee_category_id" class="form-select @error('fee_category_id') is-invalid @enderror" @isset($feeStructure) disabled @endisset @empty($feeStructure) required @endempty>
            @isset($feeStructure)
                <option value="{{ $feeStructure->fee_category_id }}" selected>{{ $feeStructure->feeCategory->name }}</option>
            @else
                <option value="">{{ __('Select a category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('fee_category_id') === $category->id)>{{ $category->name }}</option>
                @endforeach
            @endisset
        </select>
        @isset($feeStructure)<input type="hidden" name="fee_category_id" value="{{ $feeStructure->fee_category_id }}">@endisset
        @error('fee_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="academic_year_id">{{ __('Academic Year') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="academic_year_id" name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" @isset($feeStructure) disabled @endisset @empty($feeStructure) required @endempty>
            @isset($feeStructure)
                <option value="{{ $feeStructure->academic_year_id }}" selected>{{ $feeStructure->academicYear->name }}</option>
            @else
                <option value="">{{ __('Select the current year') }}</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected((int) old('academic_year_id') === $year->id)>{{ $year->name }}</option>
                @endforeach
            @endisset
        </select>
        @isset($feeStructure)<input type="hidden" name="academic_year_id" value="{{ $feeStructure->academic_year_id }}">@endisset
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="class_id">{{ __('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" @isset($feeStructure) disabled @endisset @empty($feeStructure) required @endempty>
            @isset($feeStructure)
                <option value="{{ $feeStructure->class_id }}" selected>{{ $feeStructure->schoolClass->name }}</option>
            @else
                <option value="">{{ __('Select a class') }}</option>
                @foreach ($classes as $schoolClass)
                    <option value="{{ $schoolClass->id }}" @selected((int) old('class_id') === $schoolClass->id)>{{ $schoolClass->name }} ({{ $schoolClass->code }})</option>
                @endforeach
            @endisset
        </select>
        @isset($feeStructure)<input type="hidden" name="class_id" value="{{ $feeStructure->class_id }}">@endisset
        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="amount">{{ __('Amount') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', isset($feeStructure) ? $feeStructure->amount : '') }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="frequency">{{ __('Frequency') }} <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="frequency" name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
            @foreach (['one_time' => __('One Time'), 'monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'annual' => __('Annual')] as $value => $label)
                <option value="{{ $value }}" @selected(old('frequency', $feeStructure->frequency ?? 'one_time') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="due_date">{{ __('Due Date') }}</label>
        <input id="due_date" name="due_date" type="date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date', isset($feeStructure) && $feeStructure->due_date ? $feeStructure->due_date->format('Y-m-d') : '') }}">
        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
