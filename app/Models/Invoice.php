<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'number',
        'type',
        'status',
        'amount',
        'currency',
        'plan_name',
        'duration_days',
        'description',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isPayable(): bool
    {
        return $this->status->isPayable();
    }

    public function hasPendingPayment(): bool
    {
        return $this->payments()->where('status', 'pending')->exists();
    }
}
