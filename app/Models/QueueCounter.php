<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class QueueCounter extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'queue_date',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'last_number' => 'integer',
        ];
    }
}
