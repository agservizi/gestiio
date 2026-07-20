<?php

namespace App\Enums;

enum SendApplicantType: string
{
    case DESTINATARIO = 'destinatario';
    case DELEGATO = 'delegato';
    case IMPRESA = 'impresa';
    case DELEGATO_IMPRESA = 'delegato_impresa';

    public function label(): string
    {
        return match ($this) {
            self::DESTINATARIO => 'Destinatario persona fisica',
            self::DELEGATO => 'Delegato di persona fisica',
            self::IMPRESA => 'Titolare / legale rappresentante impresa',
            self::DELEGATO_IMPRESA => 'Delegato di impresa',
        };
    }

    /** @return list<string> checklist codes required */
    public function requiredChecklistCodes(): array
    {
        return match ($this) {
            self::DESTINATARIO => [
                'avviso_send',
                'documento_destinatario',
                'cf_destinatario',
            ],
            self::DELEGATO => [
                'avviso_send',
                'documento_destinatario',
                'cf_destinatario',
                'delega',
                'documento_delegato',
                'cf_delegato',
            ],
            self::IMPRESA => [
                'avviso_send',
                'documento_rappresentante',
                'cf_rappresentante',
                'dati_impresa',
                'poteri_rappresentanza',
            ],
            self::DELEGATO_IMPRESA => [
                'avviso_send',
                'delega',
                'documento_delegato',
                'cf_delegato',
                'dati_impresa',
                'poteri_rappresentanza',
            ],
        };
    }
}
