<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Aktivasi',
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
            self::Archived => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'emerald',
            self::Suspended => 'rose',
            self::Archived => 'zinc',
        };
    }
}
