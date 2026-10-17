<?php

namespace App\Models;

use App\Enums\SubscriptionEventType;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'status',
        'price',
        'currency',
        'duration_days',
        'starts_at',
        'ends_at',
        'auto_renew',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->ends_at->startOfDay(), false);
    }

    public function recordEvent(SubscriptionEventType $type, array $attributes = []): SubscriptionEvent
    {
        return $this->events()->create(array_merge([
            'tenant_id' => $this->tenant_id,
            'type' => $type,
        ], $attributes));
    }
}
