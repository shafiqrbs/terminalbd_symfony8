<?php

declare(strict_types=1);

/**
 * Finds path()/url() calls in templates naming a route that does not exist,
 * and classifies each one against the legacy tree.
 *
 * The earlier version of this script asserted that none of these names existed
 * in the legacy tree either. It never checked. That is the same shape as §5.35 -
 * ReportBundle's routes "did not exist" for three sweeps because nothing looked
 * at how they were declared - so the claim is now computed, per name, and the
 * script gates on the one class that matters.
 *
 * Each dead name lands in exactly one bucket:
 *
 *   PORT DEFECT   the legacy router registers the name and next does not.
 *                 A link that worked in production and does not now. Gates.
 *   legacy-dead   neither router registers it, and the legacy template carries
 *                 the same call. A 500 in production today, unchanged by the
 *                 port: restoring the controller is a product decision.
 *   unreachable   the only templates naming it are ones no controller renders
 *                 and no template pulls in - vendored copies, mostly. Cannot
 *                 500 anything because nothing renders them.
 *
 * The legacy route set is resolved the way Symfony 2 resolved it: follow
 * app/config/routing.yml through every `resource:` import, in YAML and XML,
 * rather than grepping for definitions. A definition in a file nothing imports
 * is not a route - five bundles here have orphaned routing/*.yml files whose
 * names a grep happily reports.
 *
 * usage: php8.5 migration/bin/check-template-routes.php [--report=path]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
$report = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--report=')) {
        $report = substr($arg, strlen('--report='));
    }
}

/* ---------------------------------------------------------------- next routes */

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$known = [];

foreach ($kernel->getContainer()->get('router')->getRouteCollection() as $name => $route) {
    $known[$name] = true;
}

/* -------------------------------------------------------------- legacy routes */

/** Bundle class name (taken from the *Bundle.php filename) -> its directory. */
$bundleDir = [];

foreach (['/src/*/*Bundle.php', '/src/*/*/*Bundle.php', '/src/*/*/*/*Bundle.php'] as $pattern) {
    foreach (glob($root . $pattern) ?: [] as $file) {
        $bundleDir[basename($file, '.php')] = dirname($file);
    }
}

$legacy = [];
$unresolved = [];
$seen = [];

$resolveResource = static function (string $resource, string $from) use ($bundleDir, $root): ?string {
    $resource = trim($resource, " \t\"'");

    if (str_starts_with($resource, '@')) {
        $slash = strpos($resource, '/');

        if ($slash === false) {
            return null;
        }

        $bundle = substr($resource, 1, $slash - 1);

        // Vendor bundles are not on disk in this tree - composer install was
        // never run for the legacy app - so their routes are unresolvable here.
        return isset($bundleDir[$bundle]) ? $bundleDir[$bundle] . substr($resource, $slash) : null;
    }

    if (str_starts_with($resource, '%kernel.root_dir%')) {
        return $root . '/app' . substr($resource, strlen('%kernel.root_dir%'));
    }

    return str_starts_with($resource, '/') ? $resource : dirname($from) . '/' . $resource;
};

$walk = static function (string $file) use (&$walk, &$legacy, &$unresolved, &$seen, $resolveResource): void {
    $real = realpath($file);

    if ($real === false) {
        $unresolved[] = $file;

        return;
    }

    if (isset($seen[$real])) {
        return;
    }

    $seen[$real] = true;
    $body = (string) file_get_contents($real);

    if (str_ends_with($real, '.xml')) {
        if (preg_match_all('/<route\s[^>]*id="([^"]+)"/', $body, $matches)) {
            foreach ($matches[1] as $name) {
                $legacy[$name] = true;
            }
        }

        if (preg_match_all('/<import\s[^>]*resource="([^"]+)"/', $body, $matches)) {
            foreach ($matches[1] as $resource) {
                $path = $resolveResource($resource, $real);
                $path === null ? $unresolved[] = $resource : $walk($path);
            }
        }

        return;
    }

    if (!str_ends_with($real, '.yml') && !str_ends_with($real, '.yaml')) {
        return;
    }

    // A top-level key is either a route or an import; `resource:` tells them
    // apart, and an annotation/rest import names a directory, not a file.
    $key = $resource = $type = null;

    $flush = static function () use (&$key, &$resource, &$type, &$legacy, &$unresolved, &$walk, $resolveResource, $real): void {
        if ($key === null) {
            return;
        }

        if ($resource === null) {
            $legacy[$key] = true;
        } elseif ($type === 'annotation' || $type === 'rest') {
            $path = $resolveResource($resource, $real);

            foreach ($path !== null && is_dir($path)
                ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path))
                : [] as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (preg_match_all('/name\s*=\s*"([a-zA-Z0-9_]+)"/', (string) file_get_contents($file->getPathname()), $matches)) {
                    foreach ($matches[1] as $name) {
                        $legacy[$name] = true;
                    }
                }
            }

            if ($path === null || !is_dir($path)) {
                $unresolved[] = $resource;
            }
        } else {
            $path = $resolveResource($resource, $real);
            $path === null ? $unresolved[] = $resource : $walk($path);
        }

        $key = $resource = $type = null;
    };

    foreach (explode("\n", $body) as $line) {
        if (preg_match('/^([A-Za-z0-9_.]+):\s*$/', $line, $m)) {
            $flush();
            $key = $m[1];
        } elseif (preg_match('/^\s+resource:\s*(.+?)\s*$/', $line, $m)) {
            $resource = $m[1];
        } elseif (preg_match('/^\s+type:\s*(.+?)\s*$/', $line, $m)) {
            $type = trim($m[1], " \"'");
        }
    }

    $flush();
};

$walk($root . '/app/config/routing.yml');

/* --------------------------------------------------- which templates are live */

$templates = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/next/templates')) as $file) {
    if ($file->getExtension() === 'twig') {
        $templates[substr($file->getPathname(), strlen($root . '/next/templates/'))] = $file->getPathname();
    }
}

/** Templates named from PHP are the roots; everything else is reached through them. */
$reachable = [];
$queue = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/next/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    if (preg_match_all('#[\'"]([A-Za-z0-9_/@.-]+\.twig)[\'"]#', (string) file_get_contents($file->getPathname()), $matches)) {
        foreach ($matches[1] as $name) {
            $queue[] = ltrim($name, '@/');
        }
    }
}

// Twig's own entry points: the error pages and anything a bundle renders by
// convention are not named from next/src at all.
foreach (array_keys($templates) as $name) {
    if (str_starts_with($name, 'bundles/') || str_starts_with($name, 'form/')) {
        $queue[] = $name;
    }
}

while ($queue) {
    $name = array_shift($queue);

    if (isset($reachable[$name]) || !isset($templates[$name])) {
        continue;
    }

    $reachable[$name] = true;
    $body = (string) file_get_contents($templates[$name]);

    // extends/include/embed/use/import/from as tags, and include()/source() as
    // functions. Dynamic names (a variable, or ~ concatenation) resolve to
    // nothing here, so a template only they reach reads as unreachable.
    if (preg_match_all('#(?:extends|include|embed|use|import|from|source)\s*\(?\s*[\'"]([A-Za-z0-9_/@.-]+\.twig)[\'"]#', $body, $matches)) {
        foreach ($matches[1] as $ref) {
            $queue[] = ltrim($ref, '@/');
        }
    }
}

/* ------------------------------------------------------------ the dead links */

$missing = [];
$references = 0;

foreach ($templates as $name => $path) {
    $body = (string) file_get_contents($path);

    if (!preg_match_all('/(?:path|url)\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $body, $matches)) {
        continue;
    }

    foreach ($matches[1] as $route) {
        if (isset($known[$route])) {
            continue;
        }

        ++$references;
        $missing[$route][] = $name;
    }
}

ksort($missing);

/** A legacy template carrying the same call proves the link predates the port. */
$legacyViews = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() === 'twig') {
        $legacyViews[] = $file->getPathname();
    }
}

$inLegacyTemplate = [];

foreach ($legacyViews as $path) {
    if (preg_match_all('/(?:path|url)\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', (string) file_get_contents($path), $matches)) {
        foreach ($matches[1] as $route) {
            $inLegacyTemplate[$route] = true;
        }
    }
}

$buckets = ['port-defect' => [], 'legacy-dead' => [], 'unreachable' => []];

foreach ($missing as $route => $files) {
    $live = array_values(array_filter($files, static fn (string $f): bool => isset($reachable[$f])));

    if (isset($legacy[$route])) {
        $buckets['port-defect'][$route] = $files;
    } elseif ($live === []) {
        $buckets['unreachable'][$route] = $files;
    } else {
        $buckets['legacy-dead'][$route] = $live;
    }
}

printf(
    "template links to routes that do not exist: %d names, %d references\n"
    . "  port defects (registered in legacy, missing here): %d\n"
    . "  dead in the legacy tree too:                       %d\n"
    . "  only in templates nothing renders:                 %d\n",
    count($missing),
    $references,
    count($buckets['port-defect']),
    count($buckets['legacy-dead']),
    count($buckets['unreachable'])
);

printf(
    "legacy routes resolved: %d (%d files, %d unresolved vendor import(s))\n",
    count($legacy),
    count($seen),
    count(array_unique($unresolved))
);

if ($report !== null) {
    $out = "# Template links to routes that do not exist\n\n"
        . "Generated by `migration/bin/check-template-routes.php`.\n\n"
        . "`path('x')` for an undefined route throws while rendering, so one dead link takes\n"
        . "down the whole page. Each name below is classified against the legacy route set -\n"
        . "resolved by following `app/config/routing.yml` through every `resource:` import,\n"
        . "YAML and XML, rather than by grepping for definitions.\n\n"
        . sprintf(
            "- route names: **%d**\n- references: **%d**\n- port defects: **%d**\n- dead in legacy too: **%d**\n- only in unrendered templates: **%d**\n\n",
            count($missing),
            $references,
            count($buckets['port-defect']),
            count($buckets['legacy-dead']),
            count($buckets['unreachable'])
        );

    $sections = [
        'port-defect' => ["Port defects", "The legacy router registers these and this one does not. Links that worked in\nproduction and do not now."],
        'legacy-dead' => ["Dead in the legacy tree too", "Neither router registers these, and the legacy template carries the same call.\nThese pages are 500s in production today; the port did not change that. Restoring\na controller or removing a link is a product decision, so they only report."],
        'unreachable' => ["Only in templates nothing renders", "No controller renders these templates and no template pulls them in - mostly\nvendored copies kept beside the app's own. They cannot 500 a page."],
    ];

    foreach ($sections as $bucket => [$title, $blurb]) {
        $out .= "## {$title}\n\n{$blurb}\n\n";

        if ($buckets[$bucket] === []) {
            $out .= "None.\n\n";

            continue;
        }

        $out .= "```\n";

        foreach ($buckets[$bucket] as $route => $files) {
            $out .= sprintf("%-42s %d reference(s)  e.g. templates/%s\n", $route, count($files), $files[0]);
        }

        $out .= "```\n\n";
    }

    if ($unresolved !== []) {
        $out .= "## Unresolvable legacy imports\n\n"
            . "Vendor bundles are not on disk in this tree, so their route names cannot be\n"
            . "resolved from it. Every name they define is reproduced in `config/routes/auth.yaml`;\n"
            . "the FOSComment REST API deliberately is not (phase 4).\n\n```\n"
            . implode("\n", array_unique($unresolved)) . "\n```\n";
    }

    file_put_contents($report, $out);
    printf("wrote %s\n", $report);
}

exit($buckets['port-defect'] === [] ? 0 : 1);
