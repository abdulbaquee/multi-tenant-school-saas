<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('Please verify your email address before continuing. If you did not receive the email, we can send another verification link.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-outline-secondary">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
