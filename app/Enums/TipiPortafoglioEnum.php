<?php

namespace App\Enums;

enum TipiPortafoglioEnum: string
{
    case SPEDIZIONI = 'spedizioni';
    case SERVIZI = 'servizi';
    case VISURE = 'visure';

    public function testo()
    {
        return match ($this) {
            self::SPEDIZIONI => 'Spedizioni',
            self::SERVIZI => 'Servizi',
            self::VISURE => 'Visure',
        };
    }

}
