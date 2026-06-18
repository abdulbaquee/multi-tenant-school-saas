<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $school = $request->route('school');

        if (! $school instanceof School) {
            $slug = $request->header('X-School-Slug');
            $school = $slug ? School::query()->where('slug', $slug)->first() : null;
        }

        if (! $school instanceof School) {
            abort(404, 'School tenant not found.');
        }

        TenantContext::set($school->id);
        view()->share('tenantSchool', $school);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
