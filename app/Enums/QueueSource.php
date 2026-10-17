<?php

namespace App\Enums;

enum QueueSource: string
{
    case Online = 'online';
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::WalkIn => 'Datang Langsung',
        };
    }
}
