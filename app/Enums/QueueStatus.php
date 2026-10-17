<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case InService = 'in_service';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu',
            self::Called => 'Dipanggil',
            self::InService => 'Sedang Dilayani',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NoShow => 'Tidak Hadir',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'amber',
            self::Called => 'sky',
            self::InService => 'violet',
            self::Completed => 'emerald',
            self::Cancelled, self::NoShow => 'zinc',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Waiting, self::Called, self::InService], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow], true);
    }
}
