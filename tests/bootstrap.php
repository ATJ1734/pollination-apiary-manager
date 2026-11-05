<?php

declare(strict_types=1);

use App\Support\Database;
use App\Support\EnvLoader;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

EnvLoader::load($root . '/.env');

// Tests run against a separate database so they never touch demo data.
putenv('DB_DATABASE=' . (getenv('DB_DATABASE') . '_test'));
$_ENV['DB_DATABASE'] = getenv('DB_DATABASE');

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
