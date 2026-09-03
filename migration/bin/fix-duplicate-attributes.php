<?php
/**
 * Collapses repeated non-repeatable ORM attributes, keeping the LAST occurrence.
 *
 * Doctrine 2's AnnotationDriver re-keyed annotations by class name, so when a
 * docblock carried the same annotation twice the later one overwrote the earlier
 * and was the mapping actually in effect. PHP 8 rejects the repeat outright
 * ("Attribute ... must not be repeated"), so this reproduces the ORM 2 outcome.
 *
 * Genuinely repeatable attributes (JoinColumn, InverseJoinColumn, Index,
 * UniqueConstraint) are left alone.
 *
 * usage: php migration/bin/fix-duplicate-attributes.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: fix-duplicate-attributes.php <dir> [--dry-run]\n"); exit(1); }

require __DIR__ . '/../../next/vendor/autoload.php';

// Ask the ORM itself which attributes are repeatable rather than hardcoding a list.
$repeatable = [];
foreach (glob(__DIR__ . '/../../next/vendor/doctrine/orm/src/Mapping/*.php') as $file) {
    $name = basename($file, '.php');
    $cls  = 'Doctrine\\ORM\\Mapping\\' . $name;
    if (!class_exists($cls)) continue;
    foreach ((new ReflectionClass($cls))->getAttributes(Attribute::class) as $a) {
        if (((int) ($a->getArguments()[0] ?? 0)) & Attribute::IS_REPEATABLE) {
            $repeatable[$name] = true;
        }
    }
}

$removed = 0; $files = 0;

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $lines = file($f->getPathname(), FILE_IGNORE_NEW_LINES);
    $drop  = [];
    $run   = [];

    $flush = static function (array $run) use ($lines, $repeatable, &$drop): void {
        $seen = [];
        foreach ($run as $j) {
            if (!preg_match('/#\[ORM\\\\(\w+)/', trim($lines[$j]), $m)) continue;
            $name = $m[1];
            if (isset($repeatable[$name])) continue;
            if (isset($seen[$name])) $drop[] = $seen[$name];   // earlier one loses
            $seen[$name] = $j;
        }
    };

    foreach ($lines as $i => $l) {
        $t = trim($l);
        if (str_starts_with($t, '#[')) { $run[] = $i; continue; }
        if ($t === '' || str_starts_with($t, '*') || str_starts_with($t, '/*')) continue;
        if ($run) $flush($run);
        $run = [];
    }
    if ($run) $flush($run);

    if (!$drop) continue;
    $removed += count($drop);
    $files++;
    if ($dryRun) continue;

    file_put_contents($f->getPathname(), implode("\n", array_diff_key($lines, array_flip($drop))) . "\n");
}

printf("%s %d duplicate ORM attributes across %d files\n", $dryRun ? 'would remove' : 'removed', $removed, $files);
