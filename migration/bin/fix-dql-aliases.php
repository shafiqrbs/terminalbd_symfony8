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
$byShortName = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/next/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (!str_contains($f->getPathname(), '/Entity/')) continue;
    $rel = substr($f->getPathname(), strlen("$root/next/src/"), -4);
    $known['App\\' . str_replace('/', '\\', $rel)] = true;
    $byShortName[basename($rel)][] = 'App\\' . str_replace('/', '\\', $rel);
}

/**
 * The entity a Bundle:Entity alias names, or null.
 *
 * The bundle's own module is tried first, since that is what the alias meant.
 * A good number of these cross modules, though - BusinessBundle:MedicineParticular
 * is App\Medicine\Entity\MedicineParticular - because the port grouped entities
 * by subject rather than by the bundle that happened to query them. Where the
 * short name is unique in the tree there is only one thing it can mean, so that
 * is used; where it is ambiguous the alias is left alone, because a wrong entity
 * here is a silent wrong answer rather than an error.
 */
$resolve = static function (?string $module, string $entity) use ($known, $byShortName): ?string {
    if ($module !== null && isset($known["App\\$module\\Entity\\$entity"])) {
        return "App\\$module\\Entity\\$entity";
    }

    $candidates = array_values(array_filter(
        $byShortName[$entity] ?? [],
        static fn (string $c): bool => str_contains($c, '\\Entity\\')
    ));

    return count($candidates) === 1 ? $candidates[0] : null;
};

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
        static function (array $m) use ($bundleToModule, $resolve, &$converted, &$unresolved, $path): string {
            $module = $bundleToModule[$m[2]] ?? null;
            $fqcn = $resolve($module, $m[3]);
            if ($fqcn === null) {
                $unresolved[] = "$path: {$m[2]}:{$m[3]} (no such entity in the ported tree)";
                return $m[0];
            }
            $converted++;
            return '\\' . $fqcn . '::class';
        },
        $src
    );

    // Second pass: aliases embedded in a DQL string rather than standing alone,
    //   $em->createQuery("DELETE AccountingBundle:Transaction e WHERE ...")
    // 311 of these across 68 files, almost all DELETE or UPDATE - so a
    // read-only smoke test never reaches them, and ORM 3 rejects the alias
    // outright. Rewritten through the tokenizer, because a backslash inside a
    // double-quoted string is an escape: "App\Tool\Entity\X" would embed a tab.
    $tokens = token_get_all($out);
    $rebuilt = '';

    foreach ($tokens as $token) {
        if (!is_array($token) || !in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
            $rebuilt .= is_array($token) ? $token[1] : $token;
            continue;
        }

        $text = $token[1];
        $doubleQuoted = $token[0] === T_ENCAPSED_AND_WHITESPACE || str_starts_with($text, '"');

        $text = preg_replace_callback(
            '/\b(DELETE|UPDATE|FROM|JOIN)\s+([A-Za-z]+Bundle):([A-Za-z0-9_]+)\b/',
            static function (array $m) use ($bundleToModule, $resolve, &$converted, &$unresolved, $path, $doubleQuoted): string {
                $module = $bundleToModule[$m[2]] ?? null;
                $fqcn = $resolve($module, $m[3]);

                if ($fqcn === null) {
                    $unresolved[] = "$path: {$m[2]}:{$m[3]} (no such entity in the ported tree, in DQL)";
                    return $m[0];
                }

                $converted++;

                return $m[1] . ' ' . ($doubleQuoted ? str_replace('\\', '\\\\', $fqcn) : $fqcn);
            },
            $text
        );

        $rebuilt .= $text;
    }

    $out = $rebuilt;

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
