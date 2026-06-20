<div class="row g-3">
    <div class="col-12">
        <p class="small text-body-secondary mb-0">
            <span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}
        </p>
    </div>

    <div class="col-md-8">
        <label for="name" class="form-label">
            {{ __('School Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $school->name ?? '') }}" maxlength="150" autocomplete="organization" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="code" class="form-label">
            {{ __('School Code') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="code" name="code" type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $school->code ?? '') }}" maxlength="50" autocomplete="off" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">
            {{ __('Official Email') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $school->email ?? '') }}" maxlength="150" autocomplete="email" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">{{ __('Official Phone') }}</label>
        <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $school->phone ?? '') }}" maxlength="30" autocomplete="tel">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="principal_name" class="form-label">{{ __('Principal Name') }}</label>
        <input id="principal_name" name="principal_name" type="text" class="form-control @error('principal_name') is-invalid @enderror" value="{{ old('principal_name', $school->principal_name ?? '') }}" maxlength="150" autocomplete="name">
        @error('principal_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="website" class="form-label">{{ __('Website') }}</label>
        <input id="website" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $school->website ?? '') }}" maxlength="255" placeholder="https://example.edu" autocomplete="url">
        @error('website')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label">{{ __('Address') }}</label>
        <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" autocomplete="street-address">{{ old('address', $school->address ?? '') }}</textarea>
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 col-xl-3">
        <label for="city" class="form-label">{{ __('City') }}</label>
        <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $school->city ?? '') }}" maxlength="100" autocomplete="address-level2">
        @error('city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 col-xl-3">
        <label for="state" class="form-label">{{ __('State') }}</label>
        <input id="state" name="state" type="text" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $school->state ?? '') }}" maxlength="100" autocomplete="address-level1">
        @error('state')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 col-xl-3">
        <label for="country" class="form-label">
            {{ __('Country') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="country" name="country" type="text" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $school->country ?? 'India') }}" maxlength="100" autocomplete="country-name" required>
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 col-xl-3">
        <label for="postal_code" class="form-label">{{ __('Postal Code') }}</label>
        <input id="postal_code" name="postal_code" type="text" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $school->postal_code ?? '') }}" maxlength="20" autocomplete="postal-code">
        @error('postal_code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
