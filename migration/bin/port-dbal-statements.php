<?php

declare(strict_types=1);

/**
 * DBAL 4 removed Statement::execute() and the old fetch methods.
 *
 * The raw-SQL repositories all follow the same shape:
 *
 *     $stmt = $connection->prepare($sql);
 *     $stmt->bindValue('year', $year);
 *     $stmt->execute();
 *     $rows = $stmt->fetchAll();
 *
 * In DBAL 4 execute() is private - calling it is a fatal error - and the fetch
 * methods moved onto the Result that executeQuery() returns:
 *
 *     $result = $stmt->executeQuery();
 *     $rows = $result->fetchAllAssociative();
 *
 * 172 execute() calls across 50 repositories, none of which any static check
 * can see: the method exists, it is simply not callable from outside the class.
 *
 * Doctrine ORM's Query::execute() is untouched - only variables that came from
 * a ->prepare() call are rewritten.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-dbal-statements.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

/** DBAL 4 names for the removed fetch methods. */
const FETCH = [
    'fetchAll' => 'fetchAllAssociative',
    'fetch' => 'fetchAssociative',
    'fetchColumn' => 'fetchOne',
];

$stats = ['execute' => 0, 'fetch' => 0, 'files' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, '->prepare(')) {
        continue;
    }

    $original = $code;

    // Variables assigned from ->prepare() are DBAL statements.
    preg_match_all('/\$(\w+)\s*=\s*[^;]*->prepare\(/', $code, $matches);

    foreach (array_unique($matches[1]) as $variable) {
        $quoted = preg_quote($variable, '/');

        // $stmt->execute();  ->  $stmt = $stmt->executeQuery();
        //
        // Reassigning keeps every later $stmt->fetch*() call working without
        // having to track a second variable through the method body.
        $code = preg_replace(
            '/\$' . $quoted . '->execute\(\)\s*;/',
            '$' . $variable . ' = $' . $variable . '->executeQuery();',
            $code,
            -1,
            $n,
        );
        $stats['execute'] += $n;

        foreach (FETCH as $old => $new) {
            $code = preg_replace(
                '/\$' . $quoted . '->' . $old . '\(\)/',
                '$' . $variable . '->' . $new . '()',
                $code,
                -1,
                $n,
            );
            $stats['fetch'] += $n;
        }
    }

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("DBAL statements: %d execute() calls, %d fetch calls, in %d files\n",
    $stats['execute'], $stats['fetch'], $stats['files']);
