<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class TenantContextMiddleware
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        try {
            $user = $request->user();

            if (! $user instanceof User || ! $user->canEstablishTenantContext()) {
                return $this->denyAccess($request);
            }

            if ($user->isSuperAdmin()) {
                $this->context->setPlatform();
            } else {
                $this->context->setTenant((int) $user->school_id);
            }

            return $next($request);
        } finally {
            $this->context->clear();
        }
    }

    private function denyAccess(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->withErrors([
            'email' => trans('auth.failed'),
        ]);
    }
}
