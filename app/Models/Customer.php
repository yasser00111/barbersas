<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'notes',
        'visits_count',
        'last_visit_at',
    ];

    protected function casts(): array
    {
        return [
            'visits_count' => 'integer',
            'last_visit_at' => 'datetime',
        ];
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }
}
