<?php

return [
    'api_key' => env('LOCKER_API_KEY'),
    'default_rate' => (float) env('LOCKER_DEFAULT_RATE', 3),
    'max_capacity' => (int) env('LOCKER_MAX_CAPACITY', 100),
    'code_prefix' => 'LP',
    'max_packages_per_booking' => (int) env('LOCKER_MAX_PACKAGES_PER_BOOKING', 5),
    'min_days' => (int) env('LOCKER_MIN_DAYS', 1),
    'currency' => env('LOCKER_CURRENCY', 'EUR'),
    'max_photo_kb' => (int) env('LOCKER_MAX_PHOTO_KB', 8192),
];
