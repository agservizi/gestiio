<?php

namespace App\Enums;

enum LuggageDepositStatus: string
{
    case PRENOTATO = 'PRENOTATO';
    case CHECK_IN = 'CHECK_IN';
    case COMPLETATO = 'COMPLETATO';
    case ANNULLATO = 'ANNULLATO';
    case NO_SHOW = 'NO_SHOW';

    public function label(): string
    {
        return match ($this) {
            self::PRENOTATO => 'Prenotato',
            self::CHECK_IN => 'In custodia',
            self::COMPLETATO => 'Completato',
            self::ANNULLATO => 'Annullato',
            self::NO_SHOW => 'No show',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PRENOTATO => 'badge-light-primary',
            self::CHECK_IN => 'badge-light-warning',
            self::COMPLETATO => 'badge-light-success',
            self::ANNULLATO, self::NO_SHOW => 'badge-light-danger',
        };
    }
}
