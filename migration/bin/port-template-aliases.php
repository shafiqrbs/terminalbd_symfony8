<?php

declare(strict_types=1);

/**
 * Rewrites Symfony 2's `Bundle:Path:template` references in render() calls.
 *
 * The version 2 template name is three parts separated by colons:
 *
 *     $this->render("BusinessBundle:Invoice/{$view}:index.html.twig", ...)
 *
 * resolved to `@Business/Invoice/<view>/index.html.twig` by a loader that went
 * looking for the bundle. There are no bundles left, and Symfony 8 reads that
 * string literally: it looks for a file whose NAME contains colons, does not
 * find one, and throws at render time.
 *
 * The rewrite is mechanical - drop `Bundle` from the first segment, and turn the
 * remaining colons into slashes:
 *
 *     "Business/Invoice/{$view}/index.html.twig"
 *
 * **Why these survived every earlier pass.** port-http.php converted the fully
 * literal template names, which is all but twenty-one of them. Nineteen of the
 * rest carry an INTERPOLATED variable in the middle - `{$view}`, `{$template}` -
 * so a converter matching a plain string walked past them; the last two are
 * literal and simply were not reached. All twenty-one are in Business, Hospital
 * and Customer, three modules whose data this dump does not have, which is why
 * five phases of smoke sweeps, write sweeps and screen comparisons never opened
 * one of the screens.
 *
 * That is the same shape as ReportBundle's docblock routes (§5.35) and the
 * KnpMenu aliases (§5.37): an old form nothing translates does not fail, it
 * evaporates, and it stays invisible until something exercises it.
 *
 * Two kinds are NOT rewritten, and reported instead.
 *
 * An alias built by CONCATENATION - `'FrontendBundle:' . $theme . ':login.html.twig'`
 * - is three PHP expressions, not one string, and flattening the literal parts
 * would produce a path with quotes in the middle of it.
 *
 * An alias whose target does not exist in the ported templates at all.
 * `DocudexUserBundle:Avatar:edit.html.twig` has no file behind it in either
 * tree; rewriting it would only move the error, which is the same call
 * port-knp-menus.php made about DocudexCoreBundle's builder.
 *
 * One thing this cannot reach: a reference whose variable holds a name that is
 * itself an alias. HospitalController builds
 * `$template = "Print/{$id}:{$entity->getPrintFor()}"` and then renders
 * "HospitalBundle:{$template}.html.twig" - the inner colon is a runtime value.
 * Those are print actions, outside the navigation's scope.
 *
 * usage: php8.5 migration/bin/port-template-aliases.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$src = $root . '/next/src';
$dryRun = in_array('--dry-run', array_slice($argv, 1), true);
$templates = $root . '/next/templates';

/** Does the flattened name have a file behind it, once any interpolation is allowed for? */
function resolvable(string $flat, string $templates): bool
{
    if (str_contains($flat, '{$')) {
        // The variable stands for a directory or a name; the fixed part of the
        // path is what can be checked.
        $fixed = strstr($flat, '{$', true);

        return $fixed !== false && $fixed !== '' && is_dir($templates . '/' . rtrim($fixed, '/'));
    }

    return is_file($templates . '/' . $flat);
}

/** `BusinessBundle:Invoice/{$view}:index.html.twig` -> `Business/Invoice/{$view}/index.html.twig` */
function flatten(string $alias): string
{
    $parts = explode(':', $alias);
    $parts[0] = preg_replace('/Bundle$/', '', $parts[0]);

    return implode('/', array_filter($parts, static fn (string $p): bool => $p !== ''));
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));
$changed = 0;
$rewritten = 0;
$skipped = [];

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $before = $code;

    $code = preg_replace_callback(
        // A render()/renderView() argument beginning <Something>Bundle:, in
        // either kind of quote.
        '/((?:render|renderView)\(\s*)(["\'])([A-Za-z]+Bundle:[^"\']*)\2/',
        static function (array $m) use (&$rewritten, &$skipped, $path, $templates): string {
            $quote = $m[2];
            $alias = $m[3];
            $whole = $m[0];
            $where = str_replace(dirname(__DIR__, 2) . '/', '', $path);

            // Concatenated: the literal ends before the alias does.
            if (!str_ends_with(rtrim($alias), '.twig')) {
                $skipped[] = sprintf('%s: %s (built by concatenation)', $where, $alias);

                return $whole;
            }

            $flat = flatten($alias);

            if (!resolvable($flat, $templates)) {
                $skipped[] = sprintf('%s: %s (no such template in either tree)', $where, $alias);

                return $whole;
            }

            $rewritten++;

            return $m[1] . $quote . $flat . $quote;
        },
        $code
    );

    if ($code !== $before) {
        $changed++;

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf(
    "%s %d reference(s) in %d file(s)\n",
    $dryRun ? 'would rewrite' : 'rewrote',
    $rewritten,
    $changed
);

if ($skipped !== []) {
    printf("\nleft alone (%d):\n", count($skipped));

    foreach ($skipped as $line) {
        echo '  ' . $line . "\n";
    }
}
