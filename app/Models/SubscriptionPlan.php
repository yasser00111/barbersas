<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'features',
        'max_barbers',
        'max_services',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'duration_days' => 'integer',
            'max_barbers' => 'integer',
            'max_services' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function default(): ?self
    {
        return static::query()->where('is_active', true)->orderBy('sort_order')->first();
    }
}
