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
        'user' => env('BRT_USER', env('BRT_ACCOUNT_USER_ID')),
        'password' => env('BRT_PASSWORD', env('BRT_ACCOUNT_PASSWORD')),
        'pricing' => env('BRT_PRICING'),
        'sender_customer_code' => env('BRT_SENDER_CUSTOMER_CODE', env('BRT_USER', env('BRT_ACCOUNT_USER_ID'))),
        'base_url' => env('BRT_BASE_URL', env('BRT_REST_BASE_URL', 'https://api.brt.it/rest/v1')),
        'departure_depot' => env('BRT_DEPARTURE_DEPOT', 122),
        'default_network' => env('BRT_DEFAULT_NETWORK', ' '),
        'default_service_type' => env('BRT_DEFAULT_SERVICE_TYPE', ''),
        'default_pudo_id' => env('BRT_DEFAULT_PUDO_ID', ''),
        'default_delivery_freight_type_code' => env('BRT_DELIVERY_FREIGHT_TYPE_CODE', 'DAP'),
        'service_code' => env('BRT_SERVICE_CODE', ''),
        'return_service_code' => env('BRT_RETURN_SERVICE_CODE', ''),
        'return_depot' => env('BRT_RETURN_DEPOT', ''),
        'default_country' => env('BRT_DEFAULT_COUNTRY', 'IT'),
        'allowed_destination_countries' => env('BRT_ALLOWED_DESTINATION_COUNTRIES', ''),
        'pricing_condition_code' => env('BRT_PRICING_CONDITION_CODE', env('BRT_PRICING', '360')),
        'pricing_condition_code_italia' => env('BRT_PRICING_CONDITION_CODE_ITALIA', '360'),
        'pricing_condition_code_pudo' => env('BRT_PRICING_CONDITION_CODE_PUDO', '363'),
        'pricing_condition_code_dpd' => env('BRT_PRICING_CONDITION_CODE_DPD', '390'),
        'pricing_condition_code_europe' => env('BRT_PRICING_CONDITION_CODE_EUROPE', '390'),
        'pricing_condition_code_swiss' => env('BRT_PRICING_CONDITION_CODE_SWISS', '390'),
        'auto_confirm' => env('BRT_AUTO_CONFIRM', false),
        'tracking_enabled' => env('BRT_TRACKING_ENABLED', true),
        'tracking_interval_minutes' => env('BRT_TRACKING_INTERVAL_MINUTES', 30),
        'tracking_batch_size' => env('BRT_TRACKING_BATCH_SIZE', 10),
        'tracking_stale_minutes' => env('BRT_TRACKING_STALE_MINUTES', 180),
        'tracking_max_age_days' => env('BRT_TRACKING_MAX_AGE_DAYS', 15),
        'tracking_statuses' => env('BRT_TRACKING_STATUSES', 'confirmed,warning'),
        'manifest_enabled' => env('BRT_MANIFEST_ENABLED', true),
        'manifest_store_pdf' => env('BRT_MANIFEST_STORE_PDF', true),
        'manifest_endpoint' => env('BRT_MANIFEST_ENDPOINT', '/manifests/official'),
        'portal_base_url' => env('BRT_PORTAL_BASE_URL', 'https://vas.brt.it/vas99'),
        'portal_customer_code' => env('BRT_PORTAL_CUSTOMER_CODE', env('BRT_SENDER_CUSTOMER_CODE', env('BRT_USER', env('BRT_ACCOUNT_USER_ID')))),
        'api_key' => env('BRT_API_KEY'),
        'ca_bundle_path' => env('BRT_CA_BUNDLE_PATH'),
        'pudo_base_url' => env('BRT_PUDO_BASE_URL', 'https://api.brt.it/pudo/v1/open/pickup/get-pudo-by-address'),
        'pudo_auth_token' => env('BRT_PUDO_AUTH_TOKEN', env('BRT_PUDO_API_AUTH')),
        'pudo_allowed_ips' => env('BRT_PUDO_ALLOWED_IPS'),
        'orm_base_url' => env('BRT_ORM_BASE_URL'),
        'orm_api_key' => env('BRT_ORM_API_KEY'),
        'label_required' => env('BRT_LABEL_REQUIRED', true),
        'label_output_type' => env('BRT_LABEL_OUTPUT_TYPE', 'PDF'),
        'label_offset_x' => env('BRT_LABEL_OFFSET_X', 0),
        'label_offset_y' => env('BRT_LABEL_OFFSET_Y', 0),
        'label_border' => env('BRT_LABEL_BORDER', false),
        'label_logo' => env('BRT_LABEL_LOGO', false),
        'label_barcode_row' => env('BRT_LABEL_BARCODE_ROW', false),
    ]

];
