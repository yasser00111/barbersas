<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Manual = 'manual';
    case Midtrans = 'midtrans';
    case Duitku = 'duitku';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Transfer Manual',
            self::Midtrans => 'Pembayaran Online (Midtrans)',
            self::Duitku => 'Pembayaran Online (Duitku)',
        };
    }
}
