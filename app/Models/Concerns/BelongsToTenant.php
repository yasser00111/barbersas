<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            /** @var TenantContext $context */
            $context = app(TenantContext::class);

            if (! $context->shouldScope()) {
                return;
            }

            $builder->where(
                $builder->getModel()->qualifyColumn('tenant_id'),
                $context->id(),
            );
        });

        static::creating(function ($model) {
            /** @var TenantContext $context */
            $context = app(TenantContext::class);

            if ($model->tenant_id === null && $context->has()) {
                $model->tenant_id = $context->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Query across every tenant regardless of the active context.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
