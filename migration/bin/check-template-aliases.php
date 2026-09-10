<?php

declare(strict_types=1);

/**
 * No render() call may still name its template the Symfony 2 way.
 *
 * `"BusinessBundle:Invoice/{$view}:index.html.twig"` resolved through a bundle
 * lookup that no longer exists; Symfony 8 reads it literally, finds no file
 * called that, and throws at render time. Nineteen of these survived five phases
 * of sweeps because they interpolate a variable - so the converter that handled
 * the literal ones walked past them - and because every one is in a module this
 * dump has no data for, so nothing ever opened the screen.
 *
 * This is the guard that stops that happening again. It checks the SOURCE
 * construct rather than the rendered page, which is the whole lesson of the
 * annotation and menu-alias defects before it: a form nothing translates does
 * not fail, it evaporates.
 *
 * It fails only on an alias that COULD be rewritten - one with a template behind
 * it. Four are left in the tree on purpose: two built by concatenation, and two
 * naming DocudexUserBundle templates that exist in neither tree, where a rewrite
 * would move the error rather than fix it. Deciding that by whether the target
 * exists, rather than by a list of exceptions, is what keeps the guard honest as
 * the tree changes.
 *
 * usage: php8.5 migration/bin/check-template-aliases.php
 */

$root = dirname(__DIR__, 2);
$templates = $root . '/next/templates';
$found = [];
$dead = [];

/** The same flattening port-template-aliases.php would apply. */
$flatten = static function (string $alias): string {
    $parts = explode(':', $alias);
    $parts[0] = preg_replace('/Bundle$/', '', $parts[0]);

    return implode('/', array_filter($parts, static fn (string $p): bool => $p !== ''));
};

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/next/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $code = file_get_contents($file->getPathname());

    if (preg_match_all('/(?:render|renderView)\(\s*["\']([A-Za-z]+Bundle:[^"\']*)["\']/', $code, $m) > 0) {
        foreach ($m[1] as $alias) {
            $where = sprintf('%s: %s', str_replace($root . '/', '', $file->getPathname()), $alias);
            $flat = $flatten($alias);
            $fixed = str_contains($flat, '{$') ? strstr($flat, '{$', true) : null;
            $exists = $fixed !== null
                ? ($fixed !== '' && is_dir($templates . '/' . rtrim($fixed, '/')))
                : is_file($templates . '/' . $flat);

            if ($exists && str_ends_with(rtrim($alias), '.twig')) {
                $found[] = $where;
            } else {
                $dead[] = $where;
            }
        }
    }
}

if ($found === []) {
    printf(
        "no fixable Symfony 2 template aliases in render() calls (%d left alone, see the script)\n",
        count($dead)
    );

    exit(0);
}

printf("%d render() call(s) still name a template the Symfony 2 way:\n", count($found));

foreach ($found as $line) {
    echo '  ' . $line . "\n";
}

echo "\nrun: php8.5 migration/bin/port-template-aliases.php\n";

exit(1);
