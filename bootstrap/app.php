<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantAdmin;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user?->isSuperAdmin()) {
                return route('super-admin.dashboard');
            }

            if ($user?->isBarber()) {
                return route('barber.dashboard');
            }

            return route('admin.dashboard');
        });

        /*
         * The tenant context and account-status checks MUST run before
         * SubstituteBindings so that implicit route-model binding is already
         * scoped to the authenticated user's tenant. Otherwise a tenant could
         * load another tenant's record by its uuid (IDOR).
         */
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            EnsureUserIsActive::class,
            ResolveTenant::class,
            HandleInertiaRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'super_admin' => EnsureSuperAdmin::class,
            'tenant_admin' => EnsureTenantAdmin::class,
            'tenant.active' => EnsureTenantIsActive::class,
        ]);

        // Payment-gateway callbacks are authenticated by signature, not CSRF.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson();
        });
    })->create();
