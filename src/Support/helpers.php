<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Read an environment variable with a fallback. Every tunable setting in
     * the app (company branding, mite threshold, DB credentials) goes through
     * this one function so there is a single place that reads process env.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}

if (!function_exists('e')) {
    /** Escape a value for safe HTML output. Every template echo goes through this. */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    /** Dot-notation read of a config/*.php array, e.g. config('app.mite_threshold'). */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($cache[$file])) {
            $configPath = dirname(__DIR__, 2) . "/config/{$file}.php";
            $cache[$file] = is_file($configPath) ? require $configPath : [];
        }

        if ($path === null) {
            return $cache[$file];
        }

        return $cache[$file][$path] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /** The CSRF token for the current session, creating one if none exists yet. */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /** A hidden <input> carrying the CSRF token, for use inside <form> templates. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    /** Re-populate a form field with its previously submitted value after a validation error. */
    function old(string $field, string $default = ''): string
    {
        return e($_SESSION['_old'][$field] ?? $default);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('user_role')) {
    function user_role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }
}

if (!function_exists('is_field_tech')) {
    function is_field_tech(): bool
    {
        return user_role() === 'field_tech';
    }
}

if (!function_exists('money')) {
    /** Format a dollar amount. Templates never print rate_per_hive or totals without this. */
    function money(float|string|null $amount): string
    {
        return '$' . number_format((float) $amount, 2);
    }
}
