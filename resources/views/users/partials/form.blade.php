<div class="row g-3">
    <div class="col-12">
        <p class="small text-body-secondary mb-0">
            <span class="text-danger" aria-hidden="true">*</span> {{ __('Required fields') }}
        </p>
    </div>

    <div class="col-md-6">
        <label for="name" class="form-label">
            {{ __('Name') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" autocomplete="name" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">
            {{ __('Email') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" autocomplete="email" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">{{ __('Phone') }}</label>
        <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone ?? '') }}" autocomplete="tel">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="role_id" class="form-label">
            {{ __('Role') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
        </label>
        <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
            <option value="">{{ __('Select role') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" data-role-code="{{ $role->code }}" @selected((string) old('role_id', $user->role_id ?? '') === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if (auth()->user()->isSuperAdmin())
        <div class="col-md-6">
            <label for="school_id" class="form-label">
                {{ __('School') }}
                <span id="school-required-marker" class="d-none">
                    <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
                </span>
            </label>
            <select id="school_id" name="school_id" class="form-select @error('school_id') is-invalid @enderror">
                <option value="">{{ __('Platform user') }}</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected((string) old('school_id', $user->school_id ?? '') === (string) $school->id)>{{ $school->name }}</option>
                @endforeach
            </select>
            @error('school_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">{{ __('Required for School Admin, Teacher, and Accountant roles. Leave blank only for Super Admin.') }}</div>
        </div>
    @endif

    <div class="col-md-6">
        <label for="password" class="form-label">
            {{ isset($user) ? __('New Password (optional)') : __('Password') }}
            @if (! isset($user))
                <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
            @endif
        </label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" @if (! isset($user)) required @endif>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (isset($user))
            <div class="form-text">{{ __('Leave blank to keep the current password.') }}</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">
            {{ isset($user) ? __('Confirm New Password') : __('Confirm Password') }}
            @if (! isset($user))
                <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('required') }}</span>
            @endif
        </label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" @if (! isset($user)) required @endif>
    </div>
</div>

@if (auth()->user()->isSuperAdmin())
    <script>
        (() => {
            const role = document.getElementById('role_id');
            const school = document.getElementById('school_id');
            const marker = document.getElementById('school-required-marker');

            const syncSchoolRequirement = () => {
                const roleCode = role.selectedOptions[0]?.dataset.roleCode;
                const schoolRequired = Boolean(roleCode && roleCode !== 'super_admin');

                school.required = schoolRequired;
                school.setAttribute('aria-required', schoolRequired ? 'true' : 'false');
                marker.classList.toggle('d-none', !schoolRequired);
            };

            role.addEventListener('change', syncSchoolRequirement);
            syncSchoolRequirement();
        })();
    </script>
@endif
