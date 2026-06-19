<section class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">
            {{ __('Update Password') }}
        </h2>

        <p class="text-body-secondary">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="mb-3">
                <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                <input id="update_password_current_password" name="current_password" type="password" class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
                @foreach ($errors->updatePassword->get('current_password') as $message)
                    <div class="invalid-feedback">{{ $message }}</div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                <input id="update_password_password" name="password" type="password" class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
                @foreach ($errors->updatePassword->get('password') as $message)
                    <div class="invalid-feedback">{{ $message }}</div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @if ($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password">
                @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                    <div class="invalid-feedback">{{ $message }}</div>
                @endforeach
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

                @if (session('status') === 'password-updated')
                    <span class="text-success">{{ __('Saved.') }}</span>
                @endif
            </div>
        </form>
    </div>
</section>
