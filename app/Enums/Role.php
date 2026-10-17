<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case TenantAdmin = 'tenant_admin';
    case Barber = 'barber';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::TenantAdmin => 'Admin Barbershop',
            self::Barber => 'Barber',
        };
    }

    public function isPlatformLevel(): bool
    {
        return $this === self::SuperAdmin;
    }
}
