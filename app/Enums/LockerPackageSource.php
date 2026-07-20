<?php

namespace App\Enums;

enum LockerPackageSource: string
{
    case DESK = 'desk';
    case ONLINE = 'online';
    case API = 'api';

    public function label(): string
    {
        return match ($this) {
            self::DESK => 'Sportello',
            self::ONLINE => 'Portale',
            self::API => 'API',
        };
    }
}
