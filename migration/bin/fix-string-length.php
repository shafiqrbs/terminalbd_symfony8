<?php

declare(strict_types=1);

/**
 * DBAL 4 requires an explicit length on a VARCHAR column.
 *
 * Doctrine 2 defaulted to 255 and the legacy annotations relied on it, so 205
 * columns declare `type="string"` and nothing else. DBAL 4 throws
 * "requires the length of a VARCHAR column to be specified" instead, which
 * breaks every schema operation the moment the application talks to a database.
 *
 * With a reachable database (DATABASE_URL) the real length is read from
 * information_schema and used, so the mapping states what the column actually
 * is. Without one - CI, or a table this tenant does not have - it falls back to
 * Doctrine 2's default of 255, which is what created those columns.
 *
 * Idempotent. Usage: php8.5 migration/bin/fix-string-length.php [--dry-run]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

(new Dotenv())->bootEnv($root . '/.env');

// ---- real lengths, if a database happens to be reachable -------------------

$actual = [];

try {
    $url = parse_url((string) ($_SERVER['DATABASE_URL'] ?? ''));

    if ($url !== false && isset($url['host'], $url['path'])) {
        $database = ltrim($url['path'], '/');
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s', $url['host'], $url['port'] ?? 3306, $database),
            urldecode($url['user'] ?? ''),
            urldecode($url['pass'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
        );

        $rows = $pdo->query(
            "SELECT table_name, column_name, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = " . $pdo->quote($database) . " AND data_type = 'varchar'"
        )->fetchAll(PDO::FETCH_NUM);

        foreach ($rows as [$table, $column, $length]) {
            $actual[$table][$column] = (int) $length;
        }
    }
} catch (Throwable $e) {
    // No database: fall back to the Doctrine 2 default everywhere.
}

printf("%s\n", $actual === [] ? 'no database reachable - using Doctrine 2\'s default of 255'
    : sprintf('read %d tables from information_schema', count($actual)));

// ---- rewrite the mappings --------------------------------------------------

$stats = ['columns' => 0, 'files' => 0, 'from database' => 0];
$notDefault = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, "type: 'string'")) {
        continue;
    }

    $original = $code;

    // The table this entity maps to, so a length can be looked up.
    preg_match('/#\[ORM\\\\Table\(name: \'([^\']+)\'/', $code, $tm);
    $table = $tm[1] ?? null;

    $code = preg_replace_callback(
        '/#\[ORM\\\\Column\(([^\]]*type: \'string\'[^\]]*)\)\]/',
        static function (array $m) use (&$stats, &$notDefault, $actual, $table, $path, $root): string {
            if (str_contains($m[1], 'length:')) {
                return $m[0];
            }

            preg_match('/name: \'([^\']+)\'/', $m[1], $cm);
            $column = $cm[1] ?? null;
            $length = 255;

            if ($table !== null && $column !== null && isset($actual[$table][$column])) {
                $length = $actual[$table][$column];
                ++$stats['from database'];

                if ($length !== 255) {
                    $notDefault[] = sprintf('%s.%s is varchar(%d), not the assumed 255',
                        $table, $column, $length);
                }
            }

            ++$stats['columns'];

            return '#[ORM\Column(' . str_replace(
                "type: 'string'",
                "type: 'string', length: " . $length,
                $m[1],
            ) . ')]';
        },
        $code,
    );

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("string columns given an explicit length: %d (in %d files, %d read from the database)\n",
    $stats['columns'], $stats['files'], $stats['from database']);

foreach ($notDefault as $line) {
    echo "  {$line}\n";
}
