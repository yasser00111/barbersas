<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Active => 'Aktif',
            self::Expired => 'Kedaluwarsa',
            self::Suspended => 'Ditangguhkan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'emerald',
            self::Expired => 'rose',
            self::Suspended => 'zinc',
            self::Cancelled => 'zinc',
        };
    }

    public function grantsAccess(): bool
    {
        return $this === self::Active;
    }
}
