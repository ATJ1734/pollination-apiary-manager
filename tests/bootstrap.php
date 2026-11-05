<?php

declare(strict_types=1);

use App\Support\Database;
use App\Support\EnvLoader;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

EnvLoader::load($root . '/.env');

// Tests run against a separate database so they never touch demo data.
$testDatabase = getenv('DB_DATABASE') . '_test';
putenv('DB_DATABASE=' . $testDatabase);
$_ENV['DB_DATABASE'] = $testDatabase;

// Best-effort: create the test database if it doesn't exist yet and the DB
// user has CREATE privileges (true for local dev; a shared host's DB user
// usually won't, so this silently falls through to the connection below,
// which gives a clear error if the database genuinely doesn't exist).
try {
    $cfg = config('database');
    $bootstrapPdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']),
        $cfg['username'],
        $cfg['password'],
    );
    $bootstrapPdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
} catch (PDOException) {
    // No CREATE privilege (or no server reachable yet) -- fall through.
}

$pdo = Database::connection();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(191) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob($root . '/database/migrations/*.sql');
sort($files);
foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $applied, true)) {
        continue;
    }
    $pdo->exec(file_get_contents($file));
    $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)')->execute([$filename]);
}
