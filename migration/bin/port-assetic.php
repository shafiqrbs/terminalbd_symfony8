<?php

declare(strict_types=1);

/**
 * Assetic -> plain asset() references.
 *
 * symfony/assetic-bundle has no Symfony 3+ release, so {% stylesheets %} and
 * {% javascripts %} are unknown tags: every template carrying one throws
 * "Unknown tag" at render time.
 *
 * The blocks are uniform - a list of sources and a single <link>/<script>
 * body using {{ asset_url }} - so each becomes that body repeated once per
 * source with asset() in place of asset_url, pointing at the same URL the
 * legacy app served:
 *
 *   {% stylesheets filter='cssrewrite' '@InventoryBundle/Resources/public/css/style.css' %}
 *   <link rel="stylesheet" href="{{ asset_url }}"/>
 *   {% endstylesheets %}
 *
 *   <link rel="stylesheet" href="{{ asset('bundles/inventory/css/style.css') }}"/>
 *
 * `filter='cssrewrite'` only mattered because Assetic moved files while
 * concatenating them; serving each file from its original path makes it
 * unnecessary. Concatenation and minification are a build-time concern for
 * whoever deploys - the URLs here are the same ones the SF2 app emitted.
 *
 * The bundles' Resources/public directories are copied to next/public/bundles/
 * the way assets:install did, since Symfony 8 has no bundles to install from.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-assetic.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$next = $root . '/next';
$dryRun = in_array('--dry-run', $argv, true);

// ---- bundle class name -> the directory assets:install created ------------

$bundleDirs = [];

foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: [],
) as $file) {
    $class = basename($file, '.php');
    // Symfony 2's assets:install: strtolower(name) with a trailing "bundle"
    // removed. InventoryBundle -> inventory, SettingToolBundle -> settingtool.
    $bundleDirs[$class] = [
        'web' => preg_replace('/bundle$/', '', strtolower($class)),
        'path' => dirname($file),
    ];
}

$stats = ['blocks' => 0, 'sources' => 0, 'templates' => 0, 'unmapped' => 0, 'files copied' => 0];
$unmapped = [];

/**
 * The two removed FOS bundles keep their templates under _-prefixed
 * directories; their one asset is vendored in migration/vendor-assets.
 */
const VENDORED = [
    'FOSCommentBundle' => '_foscomment',
    'FOSUserBundle' => '_fosuser',
];

$toUrl = static function (string $source) use ($bundleDirs, &$unmapped, &$stats): ?string {
    if (preg_match('#^@(FOS\w+Bundle)/Resources/(?:public|assets)/(.+)$#', $source, $fm)) {
        return 'bundles/' . VENDORED[$fm[1]] . '/' . $fm[2];
    }

    if (str_starts_with($source, '@')) {
        if (!preg_match('#^@(\w+)/Resources/public/(.+)$#', $source, $m)) {
            $unmapped[$source] = true;
            ++$stats['unmapped'];

            return null;
        }

        if (!isset($bundleDirs[$m[1]])) {
            $unmapped[$source] = true;
            ++$stats['unmapped'];

            return null;
        }

        return 'bundles/' . $bundleDirs[$m[1]]['web'] . '/' . $m[2];
    }

    return ltrim($source, '/');
};

// ---- rewrite the templates ------------------------------------------------

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($next . '/templates')) as $file) {
    if ($file->getExtension() !== 'twig') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, '{% stylesheets') && !str_contains($code, '{% javascripts')) {
        continue;
    }

    $original = $code;

    $code = preg_replace_callback(
        '/[ \t]*\{%\s*(stylesheets|javascripts)\b(.*?)%\}(.*?)\{%\s*end\1\s*%\}[ \t]*/s',
        static function (array $m) use ($toUrl, &$stats, $bundleDirs, $root): string {
            ++$stats['blocks'];

            $body = $m[3];
            $sources = [];

            foreach (preg_split('/\s+/', trim($m[2])) as $token) {
                $token = trim($token);

                // filter='cssrewrite', output='...', combine=false and friends
                if ($token === '' || str_contains($token, '=')) {
                    continue;
                }

                $sources[] = trim($token, '\'"');
            }

            $out = [];

            foreach ($sources as $source) {
                // '@FooBundle/Resources/public/js/*' - expand against the tree.
                if (str_contains($source, '*')) {
                    if (preg_match('#^@(\w+)/(.+)$#', $source, $gm) && isset($bundleDirs[$gm[1]])) {
                        $base = $bundleDirs[$gm[1]]['path'];

                        foreach (glob($base . '/' . $gm[2]) ?: [] as $match) {
                            $relative = substr($match, strlen($base) + 1);
                            $url = $toUrl('@' . $gm[1] . '/' . $relative);

                            if ($url !== null) {
                                ++$stats['sources'];
                                $out[] = str_replace('{{ asset_url }}', "{{ asset('" . $url . "') }}", $body);
                            }
                        }
                    }

                    continue;
                }

                $url = $toUrl($source);

                if ($url === null) {
                    continue;
                }

                ++$stats['sources'];
                $out[] = str_replace('{{ asset_url }}', "{{ asset('" . $url . "') }}", $body);
            }

            return implode('', $out) ?: '';
        },
        $code,
    );

    if ($code !== $original) {
        ++$stats['templates'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

// ---- assets:install, without bundles --------------------------------------

$copy = static function (string $from, string $to) use (&$copy, &$stats, $dryRun): void {
    if (!is_dir($to) && !$dryRun) {
        mkdir($to, 0o775, true);
    }

    foreach (scandir($from) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $source = $from . '/' . $entry;
        $target = $to . '/' . $entry;

        if (is_dir($source)) {
            $copy($source, $target);
            continue;
        }

        ++$stats['files copied'];

        if (!$dryRun) {
            copy($source, $target);
        }
    }
};

// The vendored FOS assets ship in the migration tree, not in src/.
if (is_dir($root . '/migration/vendor-assets')) {
    foreach (scandir($root . '/migration/vendor-assets') ?: [] as $entry) {
        if (str_starts_with($entry, '.') || !is_dir($root . '/migration/vendor-assets/' . $entry)) {
            continue;
        }

        $copy($root . '/migration/vendor-assets/' . $entry, $next . '/public/bundles/' . $entry);
    }
}

foreach ($bundleDirs as $class => $info) {
    $public = $info['path'] . '/Resources/public';

    if (!is_dir($public)) {
        continue;
    }

    $copy($public, $next . '/public/bundles/' . $info['web']);
}

// The application's own assets, which assets:install never touched: it only
// ever published bundle Resources/public. app/Resources/assets reached web/
// by a deploy-time copy, and web/assets is gitignored - so the tree is in the
// repository but the published copy is not, in either application. 1,532
// asset('assets/...') calls resolve here; without it every page renders with
// no stylesheet at all, which reads as "the design is gone" rather than as a
// missing build step.
if (is_dir($appAssets = $root . '/app/Resources/assets')) {
    $copy($appAssets, $next . '/public/assets');
}

foreach ($stats as $label => $count) {
    printf("%-16s %d\n", $label, $count);
}

if ($unmapped !== []) {
    echo "\nsources left out (no such bundle in the tree):\n";

    foreach (array_keys($unmapped) as $source) {
        echo "    {$source}\n";
    }
}
