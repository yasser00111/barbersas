<?php

namespace App\Models;

use App\Enums\QueueSource;
use App\Enums\QueueStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    use BelongsToTenant, HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'barber_id',
        'service_id',
        'queue_date',
        'queue_number',
        'status',
        'source',
        'estimated_wait_minutes',
        'estimated_start_at',
        'called_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => QueueStatus::class,
            'source' => QueueSource::class,
            'queue_date' => 'date',
            'queue_number' => 'integer',
            'estimated_wait_minutes' => 'integer',
            'estimated_start_at' => 'datetime',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
