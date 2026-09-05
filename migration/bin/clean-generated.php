<?php

declare(strict_types=1);

/**
 * Empties the module directories under next/src before a rebuild, keeping the
 * hand-written files listed in migration/hand-written.txt.
 *
 * Replaces `rm -rf next/src/{...}`, which also deleted the replacements for
 * the abandoned bundles - they live in the same namespaces as the ported code
 * and cannot simply move elsewhere.
 *
 * usage: php8.5 migration/bin/clean-generated.php <module> [<module> ...]
 */

require __DIR__ . '/hand-written.php';

$root = dirname(__DIR__, 2) . '/next';
$kept = handWrittenFiles();
$modules = array_slice($argv, 1);

$removed = 0;
$preserved = 0;

foreach ($modules as $module) {
    $dir = $root . '/src/' . $module;

    if (!is_dir($dir)) {
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        $relative = substr($file->getPathname(), strlen($root) + 1);

        if ($file->isDir()) {
            @rmdir($file->getPathname());
            continue;
        }

        if (isset($kept[$relative])) {
            ++$preserved;
            continue;
        }

        unlink($file->getPathname());
        ++$removed;
    }

    @rmdir($dir);
}

printf("cleaned %d generated files, kept %d hand-written\n", $removed, $preserved);
