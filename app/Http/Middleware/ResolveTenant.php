<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and *validates* the tenant for the authenticated user, then stores
 * it in the TenantContext so every tenant-scoped query is isolated.
 */
class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin() && $user->tenant_id !== null) {
            $tenant = $user->tenant;

            $isMember = $tenant && $user->memberships()
                ->where('tenant_id', $tenant->getKey())
                ->where('status', 'active')
                ->exists();

            if (! $tenant || ! $isMember) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Tenant tidak ditemukan atau Anda tidak lagi menjadi anggota.');
            }

            $this->context->set($tenant);
        }

        return $next($request);
    }
}
