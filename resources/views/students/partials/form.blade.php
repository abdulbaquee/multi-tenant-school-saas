<div class="row g-3">
    <div class="col-12">
        <p class="small text-body-secondary mb-0"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p>
    </div>

    <div class="col-md-6">
        <label for="admission_no" class="form-label">{{ __('Admission Number') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        @if (isset($student))
            <div id="admission_no" class="form-control bg-body-tertiary">{{ $student->admission_no }}</div>
            <div class="form-text">{{ __('Admission identity is permanent and cannot be changed.') }}</div>
        @else
            <input id="admission_no" name="admission_no" type="text" class="form-control @error('admission_no') is-invalid @enderror" value="{{ old('admission_no') }}" maxlength="50" required>
            @error('admission_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>

    <div class="col-md-6">
        <label for="admission_date" class="form-label">{{ __('Admission Date') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="admission_date" name="admission_date" type="date" class="form-control @error('admission_date') is-invalid @enderror" value="{{ old('admission_date', isset($student) ? $student->admission_date?->toDateString() : '') }}" max="{{ now()->toDateString() }}" required>
        @error('admission_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="first_name" class="form-label">{{ __('First Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="first_name" name="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $student->first_name ?? '') }}" maxlength="100" required autocomplete="given-name">
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="last_name" class="form-label">{{ __('Last Name') }}</label>
        <input id="last_name" name="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $student->last_name ?? '') }}" maxlength="100" autocomplete="family-name">
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="gender" class="form-label">{{ __('Gender') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
            <option value="">{{ __('Select gender') }}</option>
            <option value="male" @selected(old('gender', $student->gender ?? '') === 'male')>{{ __('Male') }}</option>
            <option value="female" @selected(old('gender', $student->gender ?? '') === 'female')>{{ __('Female') }}</option>
            <option value="other" @selected(old('gender', $student->gender ?? '') === 'other')>{{ __('Other') }}</option>
            <option value="prefer_not_to_say" @selected(old('gender', $student->gender ?? '') === 'prefer_not_to_say')>{{ __('Prefer not to say') }}</option>
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="date_of_birth" class="form-label">{{ __('Date of Birth') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="date_of_birth" name="date_of_birth" type="date" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', isset($student) ? $student->date_of_birth?->toDateString() : '') }}" max="{{ now()->subDay()->toDateString() }}" required autocomplete="bday">
        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><hr class="my-1"><h2 class="h5 mb-0">{{ __('Guardian Information') }}</h2></div>

    <div class="col-md-6">
        <label for="guardian_name" class="form-label">{{ __('Guardian Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="guardian_name" name="guardian_name" type="text" class="form-control @error('guardian_name') is-invalid @enderror" value="{{ old('guardian_name', $student->guardian_name ?? '') }}" maxlength="150" required>
        @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="guardian_phone" class="form-label">{{ __('Guardian Phone') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="guardian_phone" name="guardian_phone" type="tel" class="form-control @error('guardian_phone') is-invalid @enderror" value="{{ old('guardian_phone', $student->guardian_phone ?? '') }}" maxlength="30" required autocomplete="tel">
        @error('guardian_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="guardian_email" class="form-label">{{ __('Guardian Email') }}</label>
        <input id="guardian_email" name="guardian_email" type="email" class="form-control @error('guardian_email') is-invalid @enderror" value="{{ old('guardian_email', $student->guardian_email ?? '') }}" maxlength="150" autocomplete="email">
        @error('guardian_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">{{ __('Residential Address') }}</label>
        <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" maxlength="2000">{{ old('address', $student->address ?? '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @unless (isset($student))
        <div class="col-12">
            <label for="photo" class="form-label">{{ __('Student Photo') }}</label>
            <input id="photo" name="photo" type="file" class="form-control @error('photo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">{{ __('Private JPG, JPEG, PNG, or WebP image. Maximum 2 MB.') }}</div>
            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endunless
</div>
