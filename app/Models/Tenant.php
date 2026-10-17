<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'email',
        'phone',
        'address',
        'city',
        'logo_path',
        'timezone',
        'status',
        'suspended_reason',
        'activated_at',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function barbers(): HasMany
    {
        return $this->hasMany(Barber::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    public function isPending(): bool
    {
        return $this->status === TenantStatus::Pending;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::Active);
    }

    public function subscriptionIsUsable(): bool
    {
        $subscription = $this->currentSubscription;

        if (! $subscription || ! $subscription->status->grantsAccess()) {
            return false;
        }

        return $subscription->ends_at === null || $subscription->ends_at->isFuture();
    }

    public function publicUrl(): string
    {
        return url('/b/'.$this->slug);
    }
}
