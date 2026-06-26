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
        'bearer_visure_camerali' => env('OPENAPI_BEARER_VISURE_CAMERALI'),
        'bearer_visure' => env('OPENAPI_BEARER_VISURE', env('OPENAPI_BEARER')),
        'bearer_catasto' => env('OPENAPI_BEARER_CATASTO'),
        'sandbox' => env('OPENAPI_SANDBOX', false),
        'visure_camerali_base_url_sandbox' => env('OPENAPI_VISURE_CAMERALI_BASE_URL_SANDBOX'),
        'visure_camerali_base_url_production' => env('OPENAPI_VISURE_CAMERALI_BASE_URL_PRODUCTION'),
        'visure_base_url_sandbox' => env('OPENAPI_VISURE_BASE_URL_SANDBOX'),
        'visure_base_url_production' => env('OPENAPI_VISURE_BASE_URL_PRODUCTION'),
        'catasto_base_url_sandbox' => env('OPENAPI_CATASTO_BASE_URL_SANDBOX'),
        'catasto_base_url_production' => env('OPENAPI_CATASTO_BASE_URL_PRODUCTION'),
    ],

    'webpush' => [
        'vapid' => [
            'public_key' => env('WEBPUSH_VAPID_PUBLIC_KEY'),
            'private_key' => env('WEBPUSH_VAPID_PRIVATE_KEY'),
            'subject' => env('WEBPUSH_VAPID_SUBJECT', 'mailto:dev@example.com'),
        ],
    ],

    'n8n' => [
        'enabled' => env('N8N_AUTOMATION_ENABLED', false),
        'webhook_url' => env('N8N_GESTIIO_WEBHOOK_URL'),
        'webhook_secret' => env('N8N_GESTIIO_WEBHOOK_SECRET'),
        'callback_secret' => env('N8N_GESTIIO_CALLBACK_SECRET', env('N8N_GESTIIO_WEBHOOK_SECRET')),
        'timeout' => env('N8N_GESTIIO_TIMEOUT', 12),
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
    ],

    'inpost' => [
        'client_id' => env('INPOST_CLIENT_ID'),
        'client_secret' => env('INPOST_CLIENT_SECRET'),
        'scope' => env('INPOST_SCOPE', 'openid api:points:read api:shipments:read api:shipments:write api:tracking:read'),
        'base_url' => env('INPOST_BASE_URL', 'https://api.inpost-group.com'),
        'token_url' => env('INPOST_TOKEN_URL', 'https://api.inpost-group.com/oauth2/token'),
        'organization_id' => env('INPOST_ORGANIZATION_ID'),
        'location_endpoint' => env('INPOST_LOCATION_ENDPOINT', '/location/v1/points'),
        'account_endpoint' => env('INPOST_ACCOUNT_ENDPOINT', '/account/v1/organizations'),
        'deposits_endpoint' => env('INPOST_DEPOSITS_ENDPOINT', '/deposits/v1/organizations/{organizationId}/deposits'),
        'tracking_endpoint_template' => env('INPOST_TRACKING_ENDPOINT_TEMPLATE', '/tracking/v1/shipments/{trackingNumber}'),
        'label_endpoint_template' => env('INPOST_LABEL_ENDPOINT_TEMPLATE', '/shipping/v2/organizations/{organizationId}/shipments/{shipmentId}/label'),
        'shipment_read_endpoint_template' => env('INPOST_SHIPMENT_READ_ENDPOINT_TEMPLATE', '/shipping/v2/organizations/{organizationId}/shipments/{shipmentId}'),
        'return_read_endpoint_template' => env('INPOST_RETURN_READ_ENDPOINT_TEMPLATE', '/returns/v2/organizations/{organizationId}/returns/{returnId}'),
        'pickup_list_endpoint_template' => env('INPOST_PICKUP_LIST_ENDPOINT_TEMPLATE', '/pickups/v1/organizations/{organizationId}/one-time-pickups'),
        'pickup_read_endpoint_template' => env('INPOST_PICKUP_READ_ENDPOINT_TEMPLATE', '/pickups/v1/organizations/{organizationId}/one-time-pickups/{orderId}'),
        'pickup_create_endpoint_template' => env('INPOST_PICKUP_CREATE_ENDPOINT_TEMPLATE', '/pickups/v1/organizations/{organizationId}/one-time-pickups'),
        'pickup_cancel_endpoint_template' => env('INPOST_PICKUP_CANCEL_ENDPOINT_TEMPLATE', '/pickups/v1/organizations/{organizationId}/one-time-pickups/{orderId}/cancel'),
        'pickup_cutoff_endpoint' => env('INPOST_PICKUP_CUTOFF_ENDPOINT', '/pickups/v1/cutoff-time'),
        'label_format' => env('INPOST_LABEL_FORMAT', 'PDF'),
        'tracking_batch_size' => env('INPOST_TRACKING_BATCH_SIZE', 10),
        'tracking_stale_minutes' => env('INPOST_TRACKING_STALE_MINUTES', 120),
        'tracking_portal_base_url' => env('INPOST_TRACKING_PORTAL_BASE_URL', 'https://inpost.pl/en/help/find-parcel'),
        'webhook_secret' => env('INPOST_WEBHOOK_SECRET'),
        'default_country' => env('INPOST_DEFAULT_COUNTRY', 'IT'),
        'point_search_limit' => env('INPOST_POINT_SEARCH_LIMIT', 20),
        'it_points_base_url' => env('INPOST_IT_POINTS_BASE_URL', 'https://api-shipx-it.easypack24.net'),
        'sender_type' => env('INPOST_SENDER_TYPE', 'address'),
        'sender_point_id' => env('INPOST_SENDER_POINT_ID'),
        'sender_name' => env('INPOST_SENDER_NAME'),
        'sender_email' => env('INPOST_SENDER_EMAIL'),
        'sender_phone' => env('INPOST_SENDER_PHONE'),
        'sender_street' => env('INPOST_SENDER_STREET'),
        'sender_building_number' => env('INPOST_SENDER_BUILDING_NUMBER'),
        'sender_post_code' => env('INPOST_SENDER_POST_CODE'),
        'sender_city' => env('INPOST_SENDER_CITY'),
        'sender_country_code' => env('INPOST_SENDER_COUNTRY_CODE', 'PL'),
        'default_headers' => env('INPOST_DEFAULT_HEADERS'),
        'defaults' => env('INPOST_DEFAULTS_JSON'),
    ],

];
