<div class="row g-3">
    <div class="col-12"><p class="small text-body-secondary mb-0"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p></div>
    <div class="col-md-6">
        <label for="class_id" class="form-label">{{ __('Class') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
            <option value="">{{ __('Select Class') }}</option>
            @foreach ($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id', $section->class_id ?? '') === (string) $class->id)>{{ $class->name }} ({{ $class->code }})</option>@endforeach
        </select>
        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="teacher_id" class="form-label">{{ __('Class Teacher') }}</label>
        <select id="teacher_id" name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror">
            <option value="">{{ __('Not assigned') }}</option>
            @foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $section->teacher_id ?? '') === (string) $teacher->id)>{{ $teacher->user->name }} ({{ $teacher->employee_code }})</option>@endforeach
        </select>
        @error('teacher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label for="name" class="form-label">{{ __('Section Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $section->name ?? '') }}" maxlength="50" placeholder="A" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="capacity" class="form-label">{{ __('Capacity') }}</label>
        <input id="capacity" name="capacity" type="number" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $section->capacity ?? '') }}" min="1" max="65535" placeholder="40">
        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
