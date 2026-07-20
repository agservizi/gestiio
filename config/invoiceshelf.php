<?php

return [
    'enabled' => (bool) env('INVOICESHELF_ENABLED', false),

    'url' => rtrim((string) env('INVOICESHELF_URL', 'http://invoiceshelf:80'), '/'),

    'token' => (string) env('INVOICESHELF_TOKEN', ''),

    'company_id' => (int) env('INVOICESHELF_COMPANY_ID', 1),

    'timeout' => (int) env('INVOICESHELF_TIMEOUT', 30),

    'customer_fornitore_id' => env('INVOICESHELF_CUSTOMER_FORNITORE_ID')
        ? (int) env('INVOICESHELF_CUSTOMER_FORNITORE_ID')
        : null,

    'fornitore' => [
        'name' => (string) env('INVOICESHELF_FORNITORE_NAME', 'Vincenzo Cinque'),
        'email' => (string) env('INVOICESHELF_FORNITORE_EMAIL', 'vincenzo@studioschettino.com'),
    ],

    /**
     * Template name for estimates used as proforma in InvoiceShelf.
     * Create via: php artisan make:template proforma (inside IS container).
     */
    'estimate_template' => env('INVOICESHELF_ESTIMATE_TEMPLATE', 'estimate1'),

    'invoice_template' => env('INVOICESHELF_INVOICE_TEMPLATE', 'invoice1'),
];
