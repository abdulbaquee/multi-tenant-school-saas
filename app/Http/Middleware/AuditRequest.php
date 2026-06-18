<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\School;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class AuditRequest
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $school = $request->route('school');
        $schoolId = TenantContext::id() ?? (($school instanceof School) ? $school->id : null);

        AuditLog::query()->withoutGlobalScopes()->create([
            'school_id' => $schoolId,
            'user_id' => $request->user()?->id,
            'action' => $request->method().' '.$request->path(),
            'auditable_type' => null,
            'auditable_id' => null,
            'metadata' => [
                'input' => $request->except(['password', 'password_confirmation']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
