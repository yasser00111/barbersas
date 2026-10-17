<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Pending => 'Menunggu Verifikasi',
            self::Paid => 'Lunas',
            self::Rejected => 'Ditolak',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::Pending => 'sky',
            self::Paid => 'emerald',
            self::Rejected => 'rose',
            self::Expired => 'zinc',
            self::Cancelled => 'zinc',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Unpaid, self::Rejected], true);
    }
}
