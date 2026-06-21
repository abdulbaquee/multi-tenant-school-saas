<div class="row g-3">
    <div class="col-12"><p class="small text-body-secondary mb-0"><span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}</p></div>

    @if (isset($eligibleUsers))
        <div class="col-12">
            <label for="user_id" class="form-label">{{ __('Teacher User') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                <option value="">{{ __('Select an eligible Teacher user') }}</option>
                @foreach ($eligibleUsers as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="col-12">
            <label class="form-label">{{ __('Teacher User') }}</label>
            <div class="form-control bg-body-tertiary">{{ $teacher->user->name }} — {{ $teacher->user->email }}</div>
            <div class="form-text">{{ __('The linked user is retained for profile history and cannot be changed.') }}</div>
        </div>
    @endif

    <div class="col-md-6">
        <label for="employee_code" class="form-label">{{ __('Employee Code') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span></label>
        <input id="employee_code" name="employee_code" type="text" class="form-control text-uppercase @error('employee_code') is-invalid @enderror" value="{{ old('employee_code', $teacher->employee_code ?? '') }}" maxlength="50" required>
        @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="joining_date" class="form-label">{{ __('Joining Date') }}</label>
        <input id="joining_date" name="joining_date" type="date" class="form-control @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', isset($teacher) ? $teacher->joining_date?->toDateString() : '') }}">
        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="qualification" class="form-label">{{ __('Qualification') }}</label>
        <input id="qualification" name="qualification" type="text" class="form-control @error('qualification') is-invalid @enderror" value="{{ old('qualification', $teacher->qualification ?? '') }}" maxlength="150">
        @error('qualification')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="specialization" class="form-label">{{ __('Specialization') }}</label>
        <input id="specialization" name="specialization" type="text" class="form-control @error('specialization') is-invalid @enderror" value="{{ old('specialization', $teacher->specialization ?? '') }}" maxlength="150">
        @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">{{ __('Academic Contact Number') }}</label>
        <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $teacher->phone ?? '') }}" maxlength="30" autocomplete="tel">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
