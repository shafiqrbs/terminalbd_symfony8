<?php

declare(strict_types=1);

/**
 * DBAL 4 requires precision and scale on a decimal column.
 *
 * Doctrine 2 defaulted them to (10, 0) and the legacy annotations relied on
 * that, so 67 columns declare `type="decimal"` and nothing else. DBAL 4 throws
 * "Column precision is not specified" instead of defaulting, which breaks every
 * schema operation - validate, diff, migrations - the moment the application
 * talks to a database.
 *
 * The live schema agrees: every decimal column in the production dump is
 * decimal(10,0). Writing that out explicitly changes no column, it just states
 * what both sides already assume.
 *
 * Idempotent. Usage: php8.5 migration/bin/fix-decimal-precision.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['columns' => 0, 'files' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php' || !str_contains($file->getPathname(), '/Entity/')) {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, "type: 'decimal'")) {
        continue;
    }

    $original = $code;

    $code = preg_replace_callback(
        '/#\[ORM\\\\Column\(([^\]]*type: \'decimal\'[^\]]*)\)\]/',
        static function (array $m) use (&$stats): string {
            if (str_contains($m[1], 'precision:')) {
                return $m[0];
            }

            ++$stats['columns'];

            // Doctrine 2's defaults, which is what the tables were created with.
            return '#[ORM\Column(' . str_replace(
                "type: 'decimal'",
                "type: 'decimal', precision: 10, scale: 0",
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

printf("decimal columns given explicit precision: %d (in %d files)\n", $stats['columns'], $stats['files']);
