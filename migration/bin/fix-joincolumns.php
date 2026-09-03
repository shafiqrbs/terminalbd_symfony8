<?php
/**
 * Removes #[ORM\JoinColumn] / #[ORM\InverseJoinColumn] from properties where it
 * is not legal: OneToMany, any inverse side (mappedBy:), and plain scalar
 * columns. Doctrine 2 ignored these; ORM 3 raises a hard mapping error.
 *
 * Owning ManyToOne/OneToOne and owning ManyToMany (inversedBy, where the pair
 * configures the implicit join table) are left untouched.
 *
 * usage: php migration/bin/fix-joincolumns.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: fix-joincolumns.php <dir> [--dry-run]\n"); exit(1); }

$removed = 0; $files = 0;

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $lines = file($f->getPathname(), FILE_IGNORE_NEW_LINES);
    $drop = [];      // line numbers (0-based) to delete
    $run  = [];      // indices of the current attribute run

    foreach ($lines as $i => $l) {
        $t = trim($l);
        if (str_starts_with($t, '#[')) { $run[] = $i; continue; }
        if ($t === '' || str_starts_with($t, '*') || str_starts_with($t, '/*')) continue;

        if ($run) {
            $text = implode(' ', array_map(static fn (int $j): string => trim($lines[$j]), $run));
            preg_match('/ORM\\\\(ManyToMany|OneToMany|ManyToOne|OneToOne)\(/', $text, $m);
            $assoc   = $m[1] ?? null;
            $inverse = str_contains($text, 'mappedBy:');

            $legal = $assoc !== null
                && !$inverse
                && in_array($assoc, ['ManyToOne', 'OneToOne', 'ManyToMany'], true);

            if (!$legal) {
                foreach ($run as $j) {
                    if (preg_match('/#\[ORM\\\\(Inverse)?JoinColumn\(/', trim($lines[$j]))) {
                        $drop[] = $j;
                    }
                }
            }
        }
        $run = [];
    }

    if (!$drop) continue;
    $removed += count($drop);
    $files++;
    if ($dryRun) continue;

    $keep = array_diff_key($lines, array_flip($drop));
    file_put_contents($f->getPathname(), implode("\n", $keep) . "\n");
}

printf("%s %d illegal JoinColumn attributes across %d files\n", $dryRun ? 'would remove' : 'removed', $removed, $files);
