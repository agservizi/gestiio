<?php

return [
    'provider' => env('SEND_PROVIDER', 'manual'),
    'integration_enabled' => (bool) env('SEND_INTEGRATION_ENABLED', false),

    'number_prefix' => env('SEND_NUMBER_PREFIX', 'SEND'),

    'assignment_method' => env('SEND_ASSIGNMENT_METHOD', 'least_open'), // least_open|round_robin|default_supervisor|manual

    'default_priority' => 'normale',

    'max_upload_kb' => (int) env('SEND_MAX_UPLOAD_KB', 20480),

    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],

    'sla' => [
        'take_charge_hours' => (int) env('SEND_SLA_TAKE_CHARGE_HOURS', 8),
        'processing_hours' => (int) env('SEND_SLA_PROCESSING_HOURS', 24),
        'integration_hours' => (int) env('SEND_SLA_INTEGRATION_HOURS', 48),
        'completion_hours' => (int) env('SEND_SLA_COMPLETION_HOURS', 72),
    ],

    'retention_days' => (int) env('SEND_RETENTION_DAYS', 0), // 0 = nessuna cancellazione automatica

    'privacy_version' => env('SEND_PRIVACY_VERSION', '2026-07-01'),

    'disk' => 'sensitive',
    'folder_prefix' => 'send',

    /** Importo mostrato all'operatore come prezzo da applicare al cittadino */
    'prezzo_cliente' => (float) env('SEND_PREZZO_CLIENTE', 5),

    /** Importo scalato dal portafoglio servizi dell'agente/admin alla creazione pratica */
    'prezzo_agente' => (float) env('SEND_PREZZO_AGENTE', 4),

    /** Importo dovuto al fornitore (Vincenzo / Studio Schettino) — solo admin, mai in UI agente */
    'importo_fornitore' => (float) env('SEND_IMPORTO_FORNITORE', 0),
];
