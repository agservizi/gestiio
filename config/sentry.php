<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Set tracesSampleRate to 1.0 to capture 100% of transactions for performance monitoring
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Capture Replay for 10% of all sessions plus 100% of sessions with an error
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    // Set to true to enable Relay mode for performance monitoring
    'enable_tracing' => true,

    // Exception types to ignore
    'ignore_exceptions' => [
        Symfony\Component\HttpKernel\Exception\HttpException::class,
    ],

    // Breadcrumbs settings
    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue' => true,
        'cache' => true,
        'http_client_requests' => true,
    ],

    // Enable metrics (use enable_metrics instead of metrics option)
    'enable_metrics' => true,
];
