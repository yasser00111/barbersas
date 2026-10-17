<?php

namespace App\Support;

use App\Models\Tenant;
use Closure;

/**
 * Holds the *validated* tenant for the current request/job.
 *
 * The tenant is never taken from user input directly: middleware resolves it
 * from the authenticated user's membership (or from an explicitly authorised
 * super-admin action) and only then is it stored here. Models using the
 * BelongsToTenant trait rely on this context for query isolation.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected bool $bypass = false;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }

    public function isBypassed(): bool
    {
        return $this->bypass;
    }

    /**
     * Run a callback with tenant isolation temporarily disabled.
     * Intended for platform-level (super admin) and scheduled jobs.
     */
    public function bypass(Closure $callback): mixed
    {
        $previous = $this->bypass;
        $this->bypass = true;

        try {
            return $callback();
        } finally {
            $this->bypass = $previous;
        }
    }

    public function shouldScope(): bool
    {
        return $this->tenant !== null && ! $this->bypass;
    }
}
