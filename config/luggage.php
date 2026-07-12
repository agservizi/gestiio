<?php

return [
    'api_key' => env('LUGGAGE_API_KEY'),
    'default_rate' => (float) env('LUGGAGE_DEFAULT_RATE', 2),
    'max_capacity' => (int) env('LUGGAGE_MAX_CAPACITY', 50),
    'code_prefix' => 'LB',
    'max_bags_per_booking' => (int) env('LUGGAGE_MAX_BAGS_PER_BOOKING', 10),
    'min_days' => (int) env('LUGGAGE_MIN_DAYS', 1),
    'currency' => env('LUGGAGE_CURRENCY', 'EUR'),
];
