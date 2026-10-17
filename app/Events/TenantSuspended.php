<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

class TenantSuspended
{
    use Dispatchable;

    public function __construct(public Tenant $tenant, public string $reason) {}
}
