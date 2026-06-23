<?php

return [
    'controlli_contratti' => [
        'title' => 'Controlli contratti',
        'desc' => 'Blocchi automatici su codice fiscale separati per Telefonia ed Energia.',
        'elements' => [
            [
                'type' => 'text',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_verifica_cf_attivo',
                'label' => 'Telefonia - Controllo CF attivo (1/0)',
                'rules' => 'nullable|in:0,1',
                'class' => '',
                'value' => '0',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_morosita',
                'label' => 'Telefonia - CF in morosita',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_blacklist',
                'label' => 'Telefonia - CF in blacklist',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_credit_check',
                'label' => 'Telefonia - CF con credit check negativo',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_morosita_per_gestore',
                'label' => 'Telefonia - Morosita per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale telefonia.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_blacklist_per_gestore',
                'label' => 'Telefonia - Blacklist per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale telefonia.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_telefonia_cf_credit_check_per_gestore',
                'label' => 'Telefonia - Credit check per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale telefonia.',
                'value' => '',
            ],
            [
                'type' => 'text',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_verifica_cf_attivo',
                'label' => 'Energia - Controllo CF attivo (1/0)',
                'rules' => 'nullable|in:0,1',
                'class' => '',
                'value' => '0',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_morosita',
                'label' => 'Energia - CF in morosita',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_blacklist',
                'label' => 'Energia - CF in blacklist',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_credit_check',
                'label' => 'Energia - CF con credit check negativo',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_morosita_per_gestore',
                'label' => 'Energia - Morosita per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale energia.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_blacklist_per_gestore',
                'label' => 'Energia - Blacklist per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale energia.',
                'value' => '',
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_energia_cf_credit_check_per_gestore',
                'label' => 'Energia - Credit check per gestore',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Formato: ID_GESTORE: CF1,CF2 (una riga per gestore). Se presente, questa regola ha priorita sul blocco generale energia.',
                'value' => '',
            ],
        ],
    ],
    //    'email' => [
    //
    //        'title' => 'Email',
    //        'desc' => 'Email settings for app',
    //        'icon' => 'glyphicon glyphicon-envelope',
    //
    //        'elements' => [
    //            [
    //                'type' => 'text', // input fields type
    //                'data' => 'string', // data type, string, int, boolean
    //                'name' => 'app_name', // unique name for field
    //                'label' => 'App Name', // you know what label it is
    //                'rules' => 'required|min:2|max:50', // validation rule of laravel
    //                'class' => 'w-auto px-2', // any class for input
    //                'value' => '' // default value if you want
    //            ]
    //        ]
    //    ],
];
