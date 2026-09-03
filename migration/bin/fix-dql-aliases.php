<?php
/**
 * Converts Doctrine 2 short-name aliases ("SettingToolBundle:GlobalOption") to
 * FQCN class constants. ORM 3 removed the short syntax entirely, so every one
 * of these is a fatal at runtime.
 *
 * Each resolved class is checked against the ported tree; anything that does
 * not resolve is reported rather than rewritten, because a wrong entity here
 * fails loudly but a *plausible* wrong one would not.
 *
 * usage: php migration/bin/fix-dql-aliases.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: fix-dql-aliases.php <dir> [--dry-run]\n"); exit(1); }

$root = dirname(__DIR__, 2);

// bundle class name -> module
$bundleToModule = [];
foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: []
) as $f) {
    $d = basename(dirname($f));
    if (str_ends_with($d, 'Bundle')) $bundleToModule[basename($f, '.php')] = substr($d, 0, -strlen('Bundle'));
}

// A bad find-replace in the legacy tree corrupted this prefix; it throws
// "Unknown Entity namespace alias" on that route today.
$bundleToModule['SettinMallShopTypegContentBundle'] = 'Content';

/** class exists in the ported tree? */
$known = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/next/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (!str_contains($f->getPathname(), '/Entity/')) continue;
    $rel = substr($f->getPathname(), strlen("$root/next/src/"), -4);
    $known['App\\' . str_replace('/', '\\', $rel)] = true;
}

$converted = 0; $files = 0; $unresolved = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = (string) file_get_contents($f->getPathname());
    if (!preg_match('/[A-Za-z]+Bundle::?[A-Za-z0-9_]+/', $src)) continue;

    $path = $f->getPathname();
    $out = preg_replace_callback(
        // a whole quoted string that is exactly Bundle:Entity (":" or the
        // malformed "::" seen in a couple of legacy files)
        '/([\'"])([A-Za-z]+Bundle)::?([A-Za-z0-9_]+)\1/',
        static function (array $m) use ($bundleToModule, $known, &$converted, &$unresolved, $path): string {
            $module = $bundleToModule[$m[2]] ?? null;
            if ($module === null) {
                $unresolved[] = "$path: unknown bundle {$m[2]}";
                return $m[0];
            }
            $fqcn = "App\\$module\\Entity\\{$m[3]}";
            if (!isset($known[$fqcn])) {
                $unresolved[] = "$path: {$m[2]}:{$m[3]} -> $fqcn (class not found)";
                return $m[0];
            }
            $converted++;
            return '\\' . $fqcn . '::class';
        },
        $src
    );

    if ($out === $src) continue;
    $files++;
    if (!$dryRun) file_put_contents($path, $out);
}

printf("%s %d short-name aliases across %d files\n",
    $dryRun ? 'would convert' : 'converted', $converted, $files);

if ($unresolved) {
    $unresolved = array_unique($unresolved);
    printf("\nUNRESOLVED (%d) - left untouched, need manual review:\n", count($unresolved));
    foreach (array_slice($unresolved, 0, 20) as $u) echo '  ' . str_replace("$root/", '', $u) . "\n";
    if (count($unresolved) > 20) printf("  ... and %d more\n", count($unresolved) - 20);
    file_put_contents("$root/migration/contract/unresolved-dql-aliases.txt", implode("\n", $unresolved) . "\n");
}
