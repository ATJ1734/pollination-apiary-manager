<?php

declare(strict_types=1);

use App\Support\EnvLoader;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

EnvLoader::load($root . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');

// Refuse to boot in production if a demo password was left at its documented default.
if (config('app.env') === 'production') {
    $defaults = [
        'DEMO_ADMIN_PASSWORD' => 'AdminDemo!2026',
        'DEMO_MANAGER_PASSWORD' => 'ManagerDemo!2026',
        'DEMO_FIELD_TECH_PASSWORD' => 'FieldDemo!2026',
    ];
    foreach ($defaults as $var => $default) {
        if (env($var) === $default) {
            http_response_code(500);
            echo 'Refusing to start: ' . $var . ' still has its documented demo value. Change it in .env.';
            exit(1);
        }
    }
}

$cookieSecure = str_starts_with((string) config('app.url'), 'https://');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $cookieSecure,
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
