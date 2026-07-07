<?php

namespace App\Enums;

enum OrdineEbikeStatoEnum: string
{
    case IN_ATTESA_PAGAMENTO = 'in_attesa_pagamento';
    case PAGAMENTO_DA_VERIFICARE = 'pagamento_da_verificare';
    case PAGAMENTO_CONFERMATO = 'pagamento_confermato';
    case SPEDITO = 'spedito';
    case CONSEGNATO = 'consegnato';
    case ANNULLATO = 'annullato';

    public function testo(): string
    {
        return match ($this) {
            self::IN_ATTESA_PAGAMENTO => 'In attesa di bonifico',
            self::PAGAMENTO_DA_VERIFICARE => 'Pagamento da verificare',
            self::PAGAMENTO_CONFERMATO => 'Pagamento confermato',
            self::SPEDITO => 'Spedito',
            self::CONSEGNATO => 'Consegnato',
            self::ANNULLATO => 'Annullato',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::IN_ATTESA_PAGAMENTO => 'badge-light-warning',
            self::PAGAMENTO_DA_VERIFICARE => 'badge-light-primary',
            self::PAGAMENTO_CONFERMATO => 'badge-light-info',
            self::SPEDITO => 'badge-light-success',
            self::CONSEGNATO => 'badge-success',
            self::ANNULLATO => 'badge-light-danger',
        };
    }
}
