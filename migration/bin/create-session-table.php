<?php

declare(strict_types=1);

/**
 * Creates the shared `sessions` table, if it does not exist.
 *
 * Additive and idempotent: it holds session payloads only, and the legacy
 * application keeps working whether or not it is present - until its own
 * handler is pointed at it. See migration/session-bridge.md.
 *
 * usage: php8.5 migration/bin/create-session-table.php
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

$root = dirname(__DIR__, 2);

(new Dotenv())->bootEnv($root . '/next/.env');

$url = $_SERVER['DATABASE_URL'] ?? null;

if ($url === null) {
    fwrite(STDERR, "DATABASE_URL is not set\n");
    exit(2);
}

$handler = new PdoSessionHandler($url, [
    'db_table' => 'sessions',
    'db_id_col' => 'sess_id',
    'db_data_col' => 'sess_data',
    'db_lifetime_col' => 'sess_lifetime',
    'db_time_col' => 'sess_time',
]);

try {
    $handler->createTable();
    echo "created table `sessions`\n";
} catch (Throwable $e) {
    if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'Base table or view already exists')) {
        echo "table `sessions` already exists\n";
        exit(0);
    }

    fwrite(STDERR, 'could not create the sessions table: ' . $e->getMessage() . "\n");
    exit(1);
}
