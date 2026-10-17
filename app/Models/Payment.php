<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant, HasFactory, HasUuid;

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'method',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'proof_path',
        'paid_at',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isSettled(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }
}
