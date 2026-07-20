<?php

namespace App\Enums;

enum SendPriority: string
{
    case NORMALE = 'normale';
    case ALTA = 'alta';
    case URGENTE = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::NORMALE => 'Normale',
            self::ALTA => 'Alta',
            self::URGENTE => 'Urgente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NORMALE => 'badge-light',
            self::ALTA => 'badge-light-warning',
            self::URGENTE => 'badge-light-danger',
        };
    }
}
