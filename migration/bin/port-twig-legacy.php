<?php

declare(strict_types=1);

/**
 * The Twig 1 / Symfony 2 constructs left in the templates.
 *
 *   {{ form_enctype(form) }}   removed in Symfony 3. It emitted
 *                              enctype="multipart/form-data" when the form
 *                              carried a file field; these five hand-written
 *                              <form> tags get that attribute directly.
 *   is not sameas(x)           Twig 2 renamed the test to `same as`.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-twig-legacy.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['form_enctype' => 0, 'sameas' => 0, 'templates' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/templates')) as $file) {
    if ($file->getExtension() !== 'twig') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;

    $code = preg_replace('/\{\{\s*form_enctype\([^)]*\)\s*\}\}/', 'enctype="multipart/form-data"', $code, -1, $n);
    $stats['form_enctype'] += $n;

    $code = preg_replace('/\bsameas\s*\(/', 'same as(', $code, -1, $n);
    $stats['sameas'] += $n;

    if ($code !== $original) {
        ++$stats['templates'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-14s %d\n", $label, $count);
}
