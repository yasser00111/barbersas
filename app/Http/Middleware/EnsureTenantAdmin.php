<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTenantAdmin() || $user->tenant_id === null) {
            abort(403, 'Akses khusus Admin Barbershop.');
        }

        return $next($request);
    }
}
