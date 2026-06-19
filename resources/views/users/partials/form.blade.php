<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">{{ __('Phone') }}</label>
        <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone ?? '') }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="role_id" class="form-label">{{ __('Role') }}</label>
        <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
            <option value="">{{ __('Select role') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id ?? '') === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if (auth()->user()->isSuperAdmin())
        <div class="col-md-6">
            <label for="school_id" class="form-label">{{ __('School') }}</label>
            <select id="school_id" name="school_id" class="form-select @error('school_id') is-invalid @enderror">
                <option value="">{{ __('Platform user') }}</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $user->school_id ?? '') === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
            @error('school_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="col-md-6">
        <label for="password" class="form-label">{{ __('Password') }}</label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" @if (! isset($user)) required @endif>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
    </div>
</div>
