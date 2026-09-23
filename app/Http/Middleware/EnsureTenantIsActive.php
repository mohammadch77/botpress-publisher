<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins may operate without an active tenant (e.g. no tenant at all).
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = app(TenantContext::class)->get();

        // No tenant context resolved at all (e.g. a super admin with no tenant_id) — allow through.
        if (! $tenant) {
            return $next($request);
        }

        if ($tenant->status !== TenantStatus::Active) {
            abort(403, 'This tenant is not active.');
        }

        return $next($request);
    }
}
