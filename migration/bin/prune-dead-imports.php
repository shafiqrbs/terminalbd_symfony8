<?php

declare(strict_types=1);

/**
 * Drops `use` statements for classes that exist nowhere.
 *
 * The legacy tree imports Frontend\FrontentBundle\Service\Cart and
 * Syndicate\Bundle\ComponentBundle\Form\VendorType - bundles that were never
 * part of this project. Unused, they are noise; used, they are a fatal, and
 * this reports them instead of pretending to fix them.
 *
 * Idempotent. Usage: php8.5 migration/bin/prune-dead-imports.php [--dry-run]
 */

/**
 * Namespaces referenced by the legacy tree that exist in neither tree: they
 * belong to bundles from other projects this code was copied between.
 */
const DEAD_NAMESPACES = [
    'Frontend\\FrontentBundle\\',
    'Syndicate\\Bundle\\',
    'Docudex\\',
];

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

require $root . '/vendor/autoload.php';

$stats = ['dropped' => 0, 'files' => 0];
$used = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;

    if (!preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;\s*$/m', $code, $matches, PREG_SET_ORDER)) {
        continue;
    }

    foreach ($matches as $match) {
        $fqcn = $match[1];
        $alias = $match[2] ?? substr($fqcn, strrpos($fqcn, '\\') + 1);

        // Only the bundles that were never part of this project. A broader
        // sweep would also catch namespace imports (`use Doctrine\ORM\Mapping
        // as ORM`), which are not classes at all.
        $dead = false;

        foreach (DEAD_NAMESPACES as $prefix) {
            if (str_starts_with($fqcn, $prefix)) {
                $dead = true;
                break;
            }
        }

        if (!$dead) {
            continue;
        }

        $body = str_replace($match[0], '', $code);

        if (preg_match('/\b' . preg_quote($alias, '/') . '\b/', $body)) {
            // Referenced in the body: removing the import would turn a
            // missing class into an undefined one. Report it instead.
            $used[$fqcn][] = substr($path, strlen($root) + 1);
            continue;
        }

        $code = str_replace($match[0] . "\n", '', $code);
        ++$stats['dropped'];
    }

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("unused imports of missing classes dropped: %d (in %d files)\n", $stats['dropped'], $stats['files']);

foreach ($used as $fqcn => $files) {
    printf("  still referenced: %-58s %s\n", $fqcn, implode(', ', array_unique($files)));
}
