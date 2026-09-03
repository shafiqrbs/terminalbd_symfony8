<?php
/**
 * Ports the SF2.8 bundle routing files into next/config/routes/legacy/.
 *
 * Handles three legacy forms:
 *   - "pattern:"  -> "path:"                       (pattern was removed in SF3)
 *   - "_controller: Bundle:Ctrl:action"            -> FQCN::actionAction
 *   - resource: "@XBundle/Resources/config/..."    -> the ported file's path
 *
 * Directory structure under Resources/config/ is preserved so nested route
 * imports keep resolving.
 *
 * usage: php migration/bin/port-routing.php [--dry-run]
 */
$root   = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);
$outDir = "$root/next/config/routes/legacy";

// bundle class -> module, same derivation as port-http.php
$bundleToModule = [];
foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: []
) as $f) {
    $dir = basename(dirname($f));
    if (!str_ends_with($dir, 'Bundle')) continue;
    $bundleToModule[basename($f, '.php')] = substr($dir, 0, -strlen('Bundle'));
}

// collect every routing yml under a bundle's Resources/config, at any depth
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'yml') continue;
    $p = $f->getPathname();
    if (!preg_match('#/([A-Za-z]+Bundle)/Resources/config/(.+\.yml)$#', $p, $m)) continue;
    $body = (string) file_get_contents($p);
    $rel  = $m[2];

    // Service/validation configs are not routing and must not be imported.
    // Scoped to files outside routing/ - one bundle has a real routing/config.yml.
    $base = basename($rel);
    if (!str_contains($rel, 'routing/')
        && in_array($base, ['services.yml', 'validation.yml', 'config.yml', 'parameters.yml'], true)) {
        continue;
    }

    // Anything under a routing/ directory, or named routing*.yml, is a routing
    // file even when it is empty - and several legacy ones are. Symfony 2 let an
    // empty import through; Symfony 8 fails if the file is missing entirely.
    $isRouting = str_contains($rel, 'routing/')
        || str_starts_with($base, 'routing')
        || str_contains($body, '_controller')
        || str_contains($body, 'resource:');
    if (!$isRouting) continue;

    $files[] = [$p, substr($m[1], 0, -strlen('Bundle')), $rel, $body];
}

$ctrl = 0; $pat = 0; $res = 0; $written = 0;

foreach ($files as [$p, $module, $rel, $body]) {
    // pattern: -> path:
    $body = preg_replace('/^(\s*)pattern:/m', '$1path:', $body, -1, $n); $pat += $n;

    // _controller: Bundle:Ctrl:action -> App\Module\Controller\CtrlController::actionAction
    $body = preg_replace_callback(
        '/\b([A-Za-z]+Bundle):([A-Za-z0-9_]+):([A-Za-z0-9_]+)\b/',
        static function (array $m) use ($bundleToModule, &$ctrl): string {
            $mod = $bundleToModule[$m[1]] ?? null;
            if ($mod === null) return $m[0];
            $ctrl++;
            return sprintf('App\\%s\\Controller\\%sController::%sAction', $mod, $m[2], $m[3]);
        },
        $body
    );

    // resource: "@XBundle/Resources/config/foo.yml" -> the ported path.
    // Routing imports are resolved relative to the importing FILE; container
    // parameters such as %kernel.project_dir% are not substituted here.
    $selfDir = "$module/" . dirname($rel);
    $selfDir = $selfDir === "$module/." ? $module : $selfDir;
    $body = preg_replace_callback(
        '/resource:\s*(["\']?)@([A-Za-z]+Bundle)\/Resources\/config\/([^"\'\s]+)\1/',
        static function (array $m) use ($bundleToModule, $selfDir, &$res): string {
            $mod = $bundleToModule[$m[2]] ?? null;
            if ($mod === null) return $m[0];
            $res++;
            return sprintf("resource: '%s'", relativePath($selfDir, "$mod/{$m[3]}"));
        },
        $body
    );

    // resource: "@XBundle/Controller/YController.php" with type: annotation -
    // an attribute-route import. Point it at the ported controller and rename
    // the type, which Symfony 8 calls "attribute".
    $body = preg_replace_callback(
        '/resource:\s*(["\']?)@([A-Za-z]+Bundle)\/(Controller\/[^"\'\s]+)\1/',
        static function (array $m) use ($bundleToModule, $selfDir): string {
            $mod = $bundleToModule[$m[2]] ?? null;
            if ($mod === null) return $m[0];
            // file lives at config/routes/legacy/<selfDir>/..., so climbing to the
            // project root takes 3 fixed levels plus one per selfDir segment.
            $up = str_repeat('../', count(array_filter(explode('/', $selfDir), 'strlen')) + 3);
            return sprintf("resource: '%ssrc/%s/%s'", $up, $mod, $m[3]);
        },
        $body
    );
    $body = preg_replace('/^(\s*)type:\s*annotation\s*$/m', '$1type: attribute', $body);

    // XML route imports and imports of bundles that no longer exist are
    // superseded by config/routes/auth.yaml (see port-auth-routes.php).
    // Comment them out with the reason rather than leaving a broken import.
    $ls = explode("\n", $body);
    foreach ($ls as $i => $l) {
        if (!preg_match('/^\s*resource:\s*/', $l)) continue;
        $dead = preg_match('/\.xml[\'"]?\s*$/', $l)          // XML routing
             || preg_match('/resource:\s*[\'"]?@/', $l);      // unresolved @Bundle
        if (!$dead) continue;
        $ls[$i] = '#' . $l . '   # superseded by config/routes/auth.yaml';
        // comment the route-group key above it and any sibling keys below
        for ($j = $i - 1; $j >= 0; $j--) {
            if (trim($ls[$j]) === '') continue;
            if (str_starts_with($ls[$j], '#')) break;
            $ls[$j] = '#' . $ls[$j];
            if (preg_match('/^#[A-Za-z0-9_.]+:\s*$/', $ls[$j])) break;
        }
        for ($j = $i + 1; $j < count($ls); $j++) {
            if (trim($ls[$j]) === '' || preg_match('/^[A-Za-z0-9_.]+:/', $ls[$j])) break;
            $ls[$j] = '#' . $ls[$j];
        }
    }
    $body = implode("\n", $ls);

    // Symfony 2's YAML parser silently kept the last of a duplicated route key;
    // Symfony 8 rejects the file outright. Preserve the SF2 outcome by commenting
    // out the earlier definition. Only that route's own block is commented - a
    // naive span from the first key to the second would swallow every unrelated
    // route declared between them.
    $ls = explode("\n", $body);
    $keyLines = [];
    foreach ($ls as $i => $l) {
        if (preg_match('/^([A-Za-z0-9_.]+):\s*$/', $l, $mm)) $keyLines[] = [$i, $mm[1]];
    }
    $occurrences = [];
    foreach ($keyLines as [$i, $name]) $occurrences[$name][] = $i;

    foreach ($occurrences as $name => $lines) {
        if (count($lines) < 2) continue;
        array_pop($lines);                     // keep the last, as SF2 did
        foreach ($lines as $startLine) {
            // block runs to the next top-level key, or end of file
            $endLine = count($ls);
            foreach ($keyLines as [$i, $_]) {
                if ($i > $startLine) { $endLine = $i; break; }
            }
            for ($j = $startLine; $j < $endLine; $j++) {
                if (trim($ls[$j]) === '' || str_starts_with($ls[$j], '#')) continue;
                $ls[$j] = '#' . $ls[$j];
            }
            $ls[$startLine] = "# DUPLICATE ROUTE NAME \"$name\" - Symfony 2 kept the later definition:\n" . $ls[$startLine];
        }
    }
    $body = implode("\n", $ls);

    // A namespaced controller inside YAML double quotes makes "\C" etc. an
    // invalid escape; single quotes keep backslashes literal.
    $body = preg_replace('/"(App\\\\[^"]*)"/', "'$1'", $body);

    $target = "$outDir/$module/$rel";
    if (!$dryRun) { @mkdir(dirname($target), 0775, true); file_put_contents($target, $body); }
    $written++;
}

// ---- root routing file -----------------------------------------------------
// app/config/routing.yml is the entry point; Flex does not auto-import nested
// .yml under config/routes/, so this becomes an explicit importer.
$rootRouting = "$root/app/config/routing.yml";
if (is_file($rootRouting)) {
    $body = (string) file_get_contents($rootRouting);
    $body = preg_replace('/^(\s*)pattern:/m', '$1path:', $body);
    $body = preg_replace_callback(
        '/resource:\s*(["\']?)@([A-Za-z]+Bundle)\/Resources\/config\/([^"\'\s]+)\1/',
        static function (array $m) use ($bundleToModule): string {
            $mod = $bundleToModule[$m[2]] ?? null;
            // FOSUserBundle / FOSCommentBundle / FOSJsRoutingBundle imports point at
            // packages that no longer exist; auth routes are re-declared by
            // port-auth-routes.php instead.
            if ($mod === null) return "resource: '@@REMOVED@@/" . $m[2] . '/' . $m[3] . "'";
            return sprintf("resource: 'legacy/%s/%s'", $mod, $m[3]);
        },
        $body
    );

    // "type: rest" selects FOSRestBundle's route loader, which is gone. Every
    // file imported that way declares explicit path: entries, so the standard
    // YAML loader handles them; the line is dropped rather than the import.
    $body = preg_replace(
        '/^(\s*)type:\s*rest\s*$/m',
        '#$0   # FOSRestBundle loader removed; imported files declare explicit paths',
        $body
    );

    // Comment out imports of bundles that are gone rather than leaving a broken
    // path. The whole import block goes - its key above and every option below
    // it (prefix:, type:, defaults:) - or Symfony sees a route with no path.
    $lines = explode("\n", $body);
    $dropped = 0;
    foreach ($lines as $i => $l) {
        if (!str_contains($l, '@@REMOVED@@')) continue;
        $lines[$i] = '#' . str_replace('@@REMOVED@@/', '', $l) . '   # bundle removed in the migration';
        $dropped++;

        for ($j = $i - 1; $j >= 0; $j--) {
            if (trim($lines[$j]) === '' || str_starts_with($lines[$j], '#')) continue;
            $lines[$j] = '#' . $lines[$j];
            if (preg_match('/^#[A-Za-z0-9_.]+:\s*$/', $lines[$j])) break;
        }
        for ($j = $i + 1; $j < count($lines); $j++) {
            if (trim($lines[$j]) === '') break;
            if (preg_match('/^[A-Za-z0-9_.]+:/', $lines[$j])) break;
            if (str_starts_with($lines[$j], '#')) continue;
            $lines[$j] = '#' . $lines[$j];
        }
    }
    $body = preg_replace('/"(App\\\\[^"]*)"/', "'$1'", $body);
    $body = "# GENERATED by migration/bin/port-routing.php from app/config/routing.yml\n"
          . "# Imports every ported bundle routing file. Auth routes live in ../auth.yaml.\n\n"
          . implode("\n", $lines);

    if (!$dryRun) file_put_contents("$root/next/config/routes/legacy.yaml", $body);
    printf("wrote root importer -> next/config/routes/legacy.yaml (%d dead bundle imports commented out)\n", $dropped);
}

printf("%s %d routing files\n  %d controller refs, %d pattern->path, %d resource refs\n",
    $dryRun ? 'would write' : 'wrote', $written, $ctrl, $pat, $res);

/** Path from one directory to a target file, both relative to the legacy/ root. */
function relativePath(string $fromDir, string $toFile): string
{
    $from = array_values(array_filter(explode('/', $fromDir), 'strlen'));
    $to   = array_values(array_filter(explode('/', $toFile), 'strlen'));

    while ($from && $to && $from[0] === $to[0]) {
        array_shift($from);
        array_shift($to);
    }

    return str_repeat('../', count($from)) . implode('/', $to);
}
