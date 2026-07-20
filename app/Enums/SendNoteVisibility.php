<?php

namespace App\Enums;

enum SendNoteVisibility: string
{
    case INTERNAL = 'internal';
    case OPERATOR = 'operator';
    case CITIZEN = 'citizen';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Interna (solo supervisori)',
            self::OPERATOR => 'Operatore',
            self::CITIZEN => 'Consegnabile al cittadino',
        };
    }
}
