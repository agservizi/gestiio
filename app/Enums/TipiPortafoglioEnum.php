<?php

namespace App\Enums;

enum TipiPortafoglioEnum: string
{
    case SPEDIZIONI = 'spedizioni';
    case SERVIZI = 'servizi';

    public function testo()
    {
        return ucfirst($this->value);
    }

}
