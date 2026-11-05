<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Pollination & Apiary Manager'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', 'false') === 'true',
    'url' => env('APP_URL', 'http://localhost:8080'),
    'key' => env('APP_KEY', ''),

    // Mites per 100 bees above which a hive counts as "over threshold" on the dashboard.
    'mite_threshold' => (float) env('MITE_THRESHOLD', 3),

    // A hive not inspected within this many days shows up as overdue.
    'inspection_overdue_days' => 21,

    'per_page' => 25,
];
