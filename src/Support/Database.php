<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/** One shared PDO connection per request, built from config/database.php. */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $cfg = config('database');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset'],
            );

            self::$connection = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }

    /** Used by tests to point the singleton at a different database/connection. */
    public static function setConnection(PDO $pdo): void
    {
        self::$connection = $pdo;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
