<?php

namespace App\Models;

use App\Enums\SubscriptionEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'type',
        'from_status',
        'to_status',
        'reason',
        'meta',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => SubscriptionEventType::class,
            'meta' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
