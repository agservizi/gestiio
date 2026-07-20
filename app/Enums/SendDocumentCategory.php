<?php

namespace App\Enums;

enum SendDocumentCategory: string
{
    case AVVISO_SEND = 'avviso_send';
    case DOC_DESTINATARIO_FRONTE = 'doc_destinatario_fronte';
    case DOC_DESTINATARIO_RETRO = 'doc_destinatario_retro';
    case CF_DESTINATARIO = 'cf_destinatario';
    case DELEGA = 'delega';
    case DOC_DELEGATO_FRONTE = 'doc_delegato_fronte';
    case DOC_DELEGATO_RETRO = 'doc_delegato_retro';
    case CF_DELEGATO = 'cf_delegato';
    case POTERI_RAPPRESENTANZA = 'poteri_rappresentanza';
    case AUTOCERTIFICAZIONE = 'autocertificazione';
    case DOC_RAPPRESENTANTE = 'doc_rappresentante';
    case CF_RAPPRESENTANTE = 'cf_rappresentante';
    case VISURA = 'visura';
    case RISULTATO = 'risultato';
    case RICEVUTA = 'ricevuta';
    case AVVISO_PAGAMENTO = 'avviso_pagamento';
    case ALTRO = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::AVVISO_SEND => 'Avviso SEND',
            self::DOC_DESTINATARIO_FRONTE => 'Documento destinatario (fronte)',
            self::DOC_DESTINATARIO_RETRO => 'Documento destinatario (retro)',
            self::CF_DESTINATARIO => 'Codice fiscale / tessera sanitaria destinatario',
            self::DELEGA => 'Delega',
            self::DOC_DELEGATO_FRONTE => 'Documento delegato (fronte)',
            self::DOC_DELEGATO_RETRO => 'Documento delegato (retro)',
            self::CF_DELEGATO => 'Codice fiscale delegato',
            self::POTERI_RAPPRESENTANZA => 'Documentazione poteri di rappresentanza',
            self::AUTOCERTIFICAZIONE => 'Autocertificazione poteri',
            self::DOC_RAPPRESENTANTE => 'Documento rappresentante',
            self::CF_RAPPRESENTANTE => 'Codice fiscale rappresentante',
            self::VISURA => 'Visura / altro impresa',
            self::RISULTATO => 'Risultato lavorazione',
            self::RICEVUTA => 'Ricevuta',
            self::AVVISO_PAGAMENTO => 'Avviso di pagamento',
            self::ALTRO => 'Altro allegato',
        };
    }
}
