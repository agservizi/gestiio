<?php

return [

    'tag_title' => 'Gestiio',
    'log_rocket' => '',
    'versione' => '5.5',

    /*
     | Footer email: solo AG00001 usa il testo aziendale fisso.
     | Gli altri affiliati mostrano ragione sociale / P.IVA / codice AG.
     */
    'mail_footer_primary_codice' => 'AG00001',
    'mail_footer_primary_company' => 'AG SERVIZI · P.IVA 08442881218',

    'mostra_accessi_test' => false,
    'accessi_test' => [
        ['descrizione' => 'Demo', 'email' => 'utente@demo.com', 'password' => '123456'],
    ],

    'paginazione' => 15,

    'prezzo_visura' => [
        'ordinaria-societa-persone' => 10,
        'ordinaria-impresa-individuale' => 7,
        'ordinaria-societa-capitale' => 12,
    ],

    'primoAnno' => 2022,
    'primoMese' => 10,

    'cartella_progetto' => env('APP_NAME'),

    'aliquota_iva' => 22,

    'cssCKEditor' => [
    ],

    'loghi' => [
        'cartella' => '/loghi',
        'dimensioni' => [
            'width' => 540,
            'height' => 366,
        ],
    ],

    'allegati_contratti' => [
        'cartella' => '/allegati_contratti',
    ],
    'allegati_contratti_energia' => [
        'cartella' => '/allegati_contratti_energia',
    ],
    'allegati_ticket' => [
        'cartella' => '/allegati_ticket',
    ],
    'file_manager' => [
        'cartella' => '/file_manager',
    ],
    'cartella_locale' => 'gestio',

    /*
     | Supervisori che possono caricare contratti energia/telefonia
     | (e moduli collegati) come un affiliato. Codice = User::codiceAgente().
     */
    'supervisore_affiliato_contratti_codici' => [
        // AG00062 usa il workflow segnalazione (sotto), non il percorso affiliato pieno.
    ],

    /*
     | Supervisori in modalità segnalazione energia/telefonia:
     | form minimo + allegati obbligatori, poi sola lettura; ops chiude.
     | Codice = User::codiceAgente().
     */
    'supervisore_segnalazione_contratti_codici' => [
        'AG00062',
    ],

    'allegati_attivazioni_sim' => [
        'cartella' => '/allegati_attivazioni_sim',
    ],
    'visure_camerali' => [
        'cartella' => '/visure_camerali',
    ],
    'allegati_visure' => [
        'cartella' => '/allegati_visure',
    ],
    'allegati_tutti' => [
        'cartella' => '/allegati_tutti',
    ],

    // bk mail
    // MAIL_ENCRYPTION=ssl
    // MAIL_FROM_ADDRESS=noreply@gestiio.it
    // MAIL_FROM_NAME=Gestiio
    // MAIL_HOST=smtps.aruba.it
    // MAIL_MAILER=smtp
    // MAIL_PASSWORD=H00:00am
    // MAIL_PORT=465
    // MAIL_USERNAME=noreply@gestiio.it

];
