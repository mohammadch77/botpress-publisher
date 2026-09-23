<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins operate across all tenants — no tenant scope is applied for them.
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user && $user->tenant_id) {
            $tenant = Tenant::withoutGlobalScopes()->find($user->tenant_id);

            if ($tenant) {
                app(TenantContext::class)->set($tenant);
            }
        }

        return $next($request);
    }
}
