<?php
return [
    'app' => [
        'title' => '',
        'desc' => 'Dati generali',
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'float', // data type, string, int, boolean
                'name' => 'prezzo_documento', // unique name for field
                'label' => 'Prezzo documento', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'class' => 'importo', // any class for input
                'value' => '' // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'paypal_client_id', // unique name for field
                'label' => 'Paypal client id', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'class' => '', // any class for input
                'value' => '' // default value if you want
            ]
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
