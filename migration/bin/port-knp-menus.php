<?php
/**
 * Rewrites KnpMenu's Symfony 2 builder aliases and registers the builders.
 *
 * Templates address a menu the way KnpMenuBundle 2 allowed:
 *
 *     {{ knp_menu_render('SettingAppearanceBundle:Builder:mainMenu', ...) }}
 *
 * Its BundleMenuProvider resolved that to <Bundle>\Menu\<Class>::<method>.
 * Version 3 dropped the provider along with the bundle lookup it depended on,
 * and there are no bundles left to look up anyway - so every one of these
 * throws "Bundle ... does not exist" at render time.
 *
 * Each alias becomes a flat menu name:
 *
 *     {{ knp_menu_render('appearance_main_menu', ...) }}
 *
 * resolved by App\Migration\Menu\LegacyBuilderProvider, which is what calls
 * the builder. The obvious alternative - a knp_menu.menu_builder tag - does
 * not fit: that tag invokes `$builder->method($options)`, while the ported
 * builders keep version 2's `method($factory, $options)`. See the provider
 * for why the signature is worth preserving.
 *
 * The map is generated rather than hand-written because port-http.php rebuilds
 * the templates on every run, so the two halves have to be produced together
 * or they drift.
 *
 * The map is read from the *legacy* templates, which always carry the version 2
 * aliases, and only the rewrite touches next/templates. Deriving it from the
 * ported templates instead would work exactly once: the second run would find
 * every alias already rewritten and generate an empty map.
 *
 * An alias whose class is not in the ported tree is left exactly as it is and
 * reported: DocudexCoreBundle's builder exists in neither tree, so rewriting
 * it would only move the error.
 *
 * usage: php migration/bin/port-knp-menus.php [--dry-run]
 */
$root   = dirname(__DIR__, 2);
$next   = $root . '/next';
$dryRun = in_array('--dry-run', $argv, true);

// bundle class -> module, the same derivation port-routing.php uses.
$bundleToModule = [];

foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: []
) as $f) {
    $dir = basename(dirname($f));

    if (!str_ends_with($dir, 'Bundle')) {
        continue;
    }

    $bundleToModule[basename($f, '.php')] = substr($dir, 0, -strlen('Bundle'));
}

/** mainMenu -> main_menu */
$snake = static fn (string $s): string => strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $s));

const ALIAS_PATTERN = '/(?<fn>knp_menu_(?:render|get)\(\s*)(?<q>[\'"])(?<bundle>\w+Bundle):(?<class>\w+):(?<method>\w+)\k<q>/';

/** Every .twig under a directory, at any depth. */
$twigFiles = static function (string $dir): iterable {
    if (!is_dir($dir)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    ) as $f) {
        if ($f->getExtension() === 'twig') {
            yield $f->getPathname();
        }
    }
};

$builders = [];   // FQCN => [method => alias]
$aliases = [];    // 'Bundle:Class:method' => alias
$unresolved = [];

// ---- the map, from the legacy templates -----------------------------------

foreach ([$root . '/app/Resources/views', $root . '/src'] as $dir) {
    foreach ($twigFiles($dir) as $path) {
        $src = (string) file_get_contents($path);

        if (!str_contains($src, 'knp_menu_')) {
            continue;
        }

        preg_match_all(ALIAS_PATTERN, $src, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $legacy = "{$m['bundle']}:{$m['class']}:{$m['method']}";

            if (isset($aliases[$legacy]) || isset($unresolved[$legacy])) {
                continue;
            }

            $module = $bundleToModule[$m['bundle']] ?? null;

            // KnpMenu 2 implied the Menu namespace inside the bundle.
            $class = $module === null
                ? null
                : 'App\\' . $module . '\\Menu\\' . $m['class'];

            $file = $class === null
                ? null
                : $next . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';

            if ($file === null || !is_file($file)) {
                $unresolved[$legacy] = true;

                continue;
            }

            $alias = $snake($module) . '_' . $snake($m['method']);
            $aliases[$legacy] = $alias;
            $builders[$class][$m['method']] = $alias;
        }
    }
}

ksort($builders);

// ---- macro imports, which Twig scopes per block ---------------------------
//
// The menu templates import knp_menu.html.twig's macros inside {% block item %}
// and then call macros.attributes() from linkElement, spanElement and
// dropdownElement as well. Twig keeps an import local to the block that made
// it, so every one of those is an undefined variable - KnpMenu's own template
// imports inside each block for exactly this reason. Blocks here are flat, so
// matching to the first endblock pairs correctly.

$importsAdded = 0;
$importFiles = 0;

foreach ($twigFiles("$next/templates") as $path) {
    $src = (string) file_get_contents($path);

    if (!preg_match('/\bmacros\./', $src)) {
        continue;
    }

    // Reuse the import the template already makes, so the macro source stays
    // whatever that template chose.
    if (!preg_match('/\{%-?\s*import\s+.*?\s+as\s+macros\s*-?%\}/', $src, $im)) {
        continue;
    }

    $import = $im[0];
    $added = 0;

    $out = preg_replace_callback(
        '/(?<open>\{%-?\s*block\s+\w+\s*-?%\})(?<body>.*?)(?=\{%-?\s*endblock)/s',
        static function (array $m) use ($import, &$added): string {
            if (!preg_match('/\bmacros\./', $m['body'])
                || preg_match('/\{%-?\s*import\s+.*?\s+as\s+macros\s*-?%\}/', $m['body'])
            ) {
                return $m[0];
            }

            ++$added;

            // Match the indentation of the block's first line of content.
            $indent = preg_match('/\R([ \t]+)\S/', $m['body'], $w) ? $w[1] : '    ';

            return $m['open'] . "\n" . $indent . $import . $m['body'];
        },
        $src
    );

    if ($added === 0) {
        continue;
    }

    ++$importFiles;
    $importsAdded += $added;

    if (!$dryRun) {
        file_put_contents($path, $out);
    }
}

// ---- the rewrite, over the ported templates -------------------------------

$rewritten = 0;
$files = 0;

foreach ($twigFiles("$next/templates") as $path) {
    $src = (string) file_get_contents($path);

    if (!str_contains($src, 'knp_menu_')) {
        continue;
    }

    $hits = 0;

    $out = preg_replace_callback(
        ALIAS_PATTERN,
        static function (array $m) use ($aliases, &$hits): string {
            $legacy = "{$m['bundle']}:{$m['class']}:{$m['method']}";

            if (!isset($aliases[$legacy])) {
                return $m[0];
            }

            ++$hits;

            return $m['fn'] . $m['q'] . $aliases[$legacy] . $m['q'];
        },
        $src
    );

    if ($hits === 0) {
        continue;
    }

    ++$files;
    $rewritten += $hits;

    if (!$dryRun) {
        file_put_contents($path, $out);
    }
}

$lines = [
    '# GENERATED by migration/bin/port-knp-menus.php - do not edit by hand.',
    '#',
    '# KnpMenuBundle 3 has no bundle-alias provider. LegacyBuilderProvider',
    '# resolves the aliases below - the same ones this script writes into the',
    '# templates - and calls each builder with the factory its signature still',
    '# expects, which the knp_menu.menu_builder tag would not pass.',
    '',
    'services:',
    '    App\\Migration\\Menu\\LegacyBuilderProvider:',
    // $factory is Knp\Menu\FactoryInterface, left to autowiring.
    '        autowire: true',
    '        arguments:',
    '            $builders: !service_locator',
];

foreach (array_keys($builders) as $class) {
    $lines[] = sprintf("                '%s': '@%s'", $class, $class);
}

$lines[] = '            $map:';

$map = [];

foreach ($builders as $class => $methods) {
    foreach ($methods as $method => $alias) {
        $map[$alias] = [$class, $method];
    }
}

ksort($map);

foreach ($map as $alias => [$class, $method]) {
    $lines[] = sprintf("                %s: ['%s', '%s']", $alias, $class, $method);
}

$lines[] = "        tags: ['knp_menu.provider']";

$target = "$next/config/services_menus.yaml";

if (!$dryRun) {
    file_put_contents($target, implode("\n", $lines) . "\n");
}

printf(
    "%s %d alias(es) in %d template(s); %d builder(s) registered\n",
    $dryRun ? 'would rewrite' : 'rewrote',
    $rewritten,
    $files,
    count($builders)
);
printf(
    "%s %d per-block macro import(s) in %d template(s)\n",
    $dryRun ? 'would add' : 'added',
    $importsAdded,
    $importFiles
);

foreach ($builders as $class => $methods) {
    printf("    %s -> %s\n", $class, implode(', ', $methods));
}

if ($unresolved !== []) {
    echo "\nleft alone (no such builder in the ported tree):\n";

    foreach (array_keys($unresolved) as $alias) {
        echo "    {$alias}\n";
    }
}
