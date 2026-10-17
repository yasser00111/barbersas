<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks operational features (queues, barbers, services, reports) whenever the
 * tenant is not active or the current subscription no longer grants access.
 *
 * Billing and account pages are intentionally *not* protected by this
 * middleware so tenants can always renew and view historical data.
 */
class EnsureTenantIsActive
{
    public function __construct(protected TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->get();

        if ($tenant === null) {
            return $next($request);
        }

        $blocked = $tenant->status === TenantStatus::Suspended
            || $tenant->status === TenantStatus::Pending
            || ! $tenant->subscriptionIsUsable();

        if ($blocked) {
            $reason = match (true) {
                $tenant->status === TenantStatus::Suspended => 'suspended',
                $tenant->status === TenantStatus::Pending => 'pending',
                default => 'expired',
            };

            return redirect()
                ->route('admin.subscription.index')
                ->with('error', match ($reason) {
                    'suspended' => 'Langganan barbershop Anda ditangguhkan. Hubungi dukungan.',
                    'pending' => 'Barbershop Anda belum aktif. Selesaikan pembayaran untuk mengaktifkan fitur.',
                    default => 'Langganan Anda telah berakhir. Perpanjang untuk melanjutkan.',
                });
        }

        return $next($request);
    }
}
