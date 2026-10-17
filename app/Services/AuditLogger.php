<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function __construct(protected TenantContext $context) {}

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function log(
        string $event,
        ?Model $auditable = null,
        ?string $description = null,
        array $old = [],
        array $new = [],
        ?int $tenantId = null,
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $tenantId ?? $this->context->id(),
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000) ?: null,
        ]);
    }
}
