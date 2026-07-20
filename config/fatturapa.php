<?php

/**
 * Dati Cedente/Prestatore per export XML FatturaPA (SDI / Agenzia delle Entrate).
 * Compilare in .env: senza Partita IVA / CF l'export XML viene rifiutato.
 */
return [
    'formato_trasmissione' => env('FATTURAPA_FORMATO', 'FPR12'), // FPR12 B2B/B2C | FPA12 PA

    'tipo_documento' => env('FATTURAPA_TIPO_DOCUMENTO', 'TD01'),

    'regime_fiscale' => env('FATTURAPA_REGIME_FISCALE', 'RF01'),

    /** Codice destinatario SDI di default se il cliente non lo ha (0000000 = PEC o codice generico) */
    'codice_destinatario_default' => env('FATTURAPA_CODICE_DESTINATARIO_DEFAULT', '0000000'),

    /** Natura IVA se aliquota 0 e non specificata (es. N4 esenti art.10) */
    'natura_zero_default' => env('FATTURAPA_NATURA_ZERO', 'N4'),

    'cedente' => [
        'denominazione' => (string) env('FATTURAPA_CEDENTE_DENOMINAZIONE', ''),
        'partita_iva' => preg_replace('/\D+/', '', (string) env('FATTURAPA_CEDENTE_PARTITA_IVA', '')),
        'codice_fiscale' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) env('FATTURAPA_CEDENTE_CODICE_FISCALE', ''))),
        'indirizzo' => (string) env('FATTURAPA_CEDENTE_INDIRIZZO', ''),
        'numero_civico' => (string) env('FATTURAPA_CEDENTE_NUMERO_CIVICO', ''),
        'cap' => (string) env('FATTURAPA_CEDENTE_CAP', ''),
        'comune' => (string) env('FATTURAPA_CEDENTE_COMUNE', ''),
        'provincia' => strtoupper((string) env('FATTURAPA_CEDENTE_PROVINCIA', '')),
        'nazione' => strtoupper((string) env('FATTURAPA_CEDENTE_NAZIONE', 'IT')),
        'pec' => (string) env('FATTURAPA_CEDENTE_PEC', ''),
        'telefono' => (string) env('FATTURAPA_CEDENTE_TELEFONO', ''),
        'email' => (string) env('FATTURAPA_CEDENTE_EMAIL', ''),
    ],

    /**
     * Nomi/slug campi custom InvoiceShelf sul customer (ordine di ricerca).
     */
    'customer_field_aliases' => [
        'codice_destinatario' => ['codice_destinatario', 'codice_sdi', 'sdi', 'codice_destinatario_sdi'],
        'pec' => ['pec', 'pec_destinatario', 'email_pec'],
        'codice_fiscale' => ['codice_fiscale', 'cf', 'codice fiscale'],
        'partita_iva' => ['partita_iva', 'piva', 'partita iva', 'vat'],
    ],
];
