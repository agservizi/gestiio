<?php

namespace App\Enums;

enum LockerPackageStatus: string
{
    case PRENOTATO = 'PRENOTATO';
    case IN_GIACENZA = 'IN_GIACENZA';
    case CONSEGNATO = 'CONSEGNATO';
    case ANNULLATO = 'ANNULLATO';
    case NO_SHOW = 'NO_SHOW';

    public function label(): string
    {
        return match ($this) {
            self::PRENOTATO => 'Prenotato',
            self::IN_GIACENZA => 'In giacenza',
            self::CONSEGNATO => 'Consegnato',
            self::ANNULLATO => 'Annullato',
            self::NO_SHOW => 'No show',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PRENOTATO => 'badge-light-primary',
            self::IN_GIACENZA => 'badge-light-warning',
            self::CONSEGNATO => 'badge-light-success',
            self::ANNULLATO, self::NO_SHOW => 'badge-light-danger',
        };
    }
}
