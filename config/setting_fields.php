<?php
return [
    'controlli_contratti' => [
        'title' => 'Controlli contratti',
        'desc' => 'Blocchi automatici su codice fiscale per telefonia/energia (semaforo rosso).',
        'elements' => [
            [
                'type' => 'text',
                'data' => 'string',
                'name' => 'blocco_contratti_verifica_cf_attivo',
                'label' => 'Controllo CF attivo (1/0)',
                'rules' => 'nullable|in:0,1',
                'class' => '',
                'value' => '0'
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_cf_morosita',
                'label' => 'CF in morosita',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => ''
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_cf_blacklist',
                'label' => 'CF in blacklist',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => ''
            ],
            [
                'type' => 'textarea',
                'data' => 'string',
                'name' => 'blocco_contratti_cf_credit_check',
                'label' => 'CF con credit check negativo',
                'rules' => 'nullable|string',
                'class' => '',
                'help' => 'Inserisci uno o piu codici fiscali separati da invio, virgola o punto e virgola.',
                'value' => ''
            ],
        ]
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
