<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
        'break_start',
        'break_end',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    public static function dayName(int $day): string
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ][$day] ?? '-';
    }
}
