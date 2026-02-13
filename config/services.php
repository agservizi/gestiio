<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
        'host' => env('RESEND_HOST', 'smtp.resend.com'),
        'port' => env('RESEND_PORT', 587),
        'username' => env('RESEND_USERNAME', 'resend'),
        'encryption' => env('RESEND_ENCRYPTION', 'tls'),
    ],

    'openapi' => [
        'bearer_sms' => env('OPENAPI_BEARER_SMS'),
        'sandbox' => env('OPENAPI_SANDBOX', false)
    ],

    'brt' => [
        'user' => env('BRT_USER'),
        'password' => env('BRT_PASSWORD'),
        'pricing' => env('BRT_PRICING'),
    ]

];
