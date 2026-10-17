<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case Chargeback = 'chargeback';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Dikembalikan',
            self::Chargeback => 'Chargeback',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'emerald',
            self::Failed, self::Rejected => 'rose',
            self::Expired, self::Refunded, self::Chargeback => 'zinc',
        };
    }
}
