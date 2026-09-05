<?php
/**
 * Phase 1 of the strangler: lift the whole Doctrine entity graph out of the
 * SF2.8 bundles into the new App\ namespace in one pass.
 *
 * It has to be one pass: 48% of targetEntity references cross a bundle
 * boundary, so a partial copy leaves Doctrine unable to resolve its metadata.
 *
 * usage: php migration/bin/port-entities.php [--dry-run]
 */
$root   = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

require __DIR__ . '/hand-written.php';

$handWritten = handWrittenFiles();
$skipped = 0;

// ---- 1. legacy bundle root -> App\<Name> ------------------------------------
$map = [];
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator("$root/src", FilesystemIterator::SKIP_DOTS)
) as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (!preg_match('/^namespace\s+([A-Za-z\\\\]+);/m', (string) file_get_contents($f->getPathname()), $m)) continue;

    $parts = explode('\\', $m[1]);
    foreach ($parts as $i => $p) {
        if (!str_ends_with($p, 'Bundle') || $p === 'Bundle') continue;
        $legacyRoot = implode('\\', array_slice($parts, 0, $i + 1));
        $map[$legacyRoot] = 'App\\' . substr($p, 0, -strlen('Bundle'));
        break;
    }
}
// Longest prefix first so Appstore\Bundle\XBundle wins over any shorter match.
uksort($map, fn($a, $b) => strlen($b) <=> strlen($a));

// The tree has one namespace whose case doesn't match its directory.
foreach ($map as $legacy => $new) {
    $lc = lcfirst($legacy);
    if ($lc !== $legacy && !isset($map[$lc])) $map[$lc] = $new;
}
uksort($map, fn($a, $b) => strlen($b) <=> strlen($a));

echo "namespace map (" . count($map) . " roots):\n";
foreach ($map as $k => $v) printf("  %-45s -> %s\n", $k, $v);

// ---- 2. collect the entity layer -------------------------------------------
$files = [];
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator("$root/src", FilesystemIterator::SKIP_DOTS)
) as $f) {
    $p = $f->getPathname();
    if ($f->getExtension() !== 'php') continue;
    if (!preg_match('#/(Entity|Repository)/#', $p)) continue;
    if (str_contains($p, '/Tests/')) continue;
    $files[] = $p;
}
printf("\n%d entity/repository files to port\n", count($files));

// ---- 3. rewrite + place -----------------------------------------------------
$written = 0; $unmapped = [];
foreach ($files as $p) {
    $src = (string) file_get_contents($p);
    if (!preg_match('/^namespace\s+([A-Za-z\\\\]+);/m', $src, $m)) continue;
    $ns = $m[1];

    $newNs = null;
    foreach ($map as $legacy => $new) {
        if ($ns === $legacy || str_starts_with($ns, $legacy . '\\')) {
            $newNs = $new . substr($ns, strlen($legacy));
            break;
        }
    }
    if ($newNs === null) { $unmapped[] = $ns; continue; }

    // Rewrite every legacy FQCN: namespace line, use statements, and the
    // class strings inside annotations (targetEntity=, repositoryClass=).
    $out = $src;
    foreach ($map as $legacy => $new) {
        $out = str_replace($legacy . '\\', $new . '\\', $out);                     // bare
        $out = str_replace(
            str_replace('\\', '\\\\', $legacy) . '\\\\',
            str_replace('\\', '\\\\', $new) . '\\\\',
            $out
        );                                                                          // escaped in strings
    }

    $target = "$root/next/src/" . str_replace('\\', '/', substr($newNs, strlen('App\\'))) . '/' . basename($p);

    if (isset($handWritten[substr($target, strlen("$root/next/"))])) { $skipped++; continue; }

    if (!$dryRun) {
        @mkdir(dirname($target), 0775, true);
        file_put_contents($target, $out);
    }
    $written++;
}

printf("%s %d files -> next/src/\n", $dryRun ? 'would write' : 'wrote', $written);
if ($skipped) printf("  kept %d hand-written file(s) - see migration/hand-written.txt\n", $skipped);
if ($unmapped) {
    printf("\nUNMAPPED namespaces (%d):\n", count(array_unique($unmapped)));
    foreach (array_unique($unmapped) as $u) echo "  $u\n";
}
