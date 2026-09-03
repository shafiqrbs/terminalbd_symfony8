<?php
/**
 * Flags `$this->createForm(new XType($deps...))` call sites for human review.
 *
 * Symfony 3 removed support for passing a form type *instance*; createForm()
 * takes a class-string. Every one of these sites passes constructor
 * dependencies (EntityManager, repositories, GlobalOption), so the conversion
 * is not mechanical: each dependency has to become a form option declared in
 * that type's configureOptions(), or an injected service.
 *
 * Guessing here would produce code that looks migrated and is wrong. These are
 * marked instead, so they are greppable, countable, and fail loudly rather than
 * silently rendering the wrong form.
 *
 * usage: php migration/bin/mark-manual-forms.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: mark-manual-forms.php <dir> [--dry-run]\n"); exit(1); }

const MARKER = 'TODO(migration): SF3+ takes a form type class-string, not an instance.';

$sites = 0; $files = 0; $types = []; $touchedFiles = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $lines = file($f->getPathname(), FILE_IGNORE_NEW_LINES);
    $out = [];
    $touched = false;

    foreach ($lines as $i => $line) {
        // Two shapes crash Rector's converter and are both non-mechanical:
        //   $this->createForm(new XType($deps), ...)
        //   $builder->add('field', new XType($deps))   <- nested form type
        if (preg_match('/new\s+([A-Za-z_\\\\]*Type)\s*\(\s*(?![\)\s])(.*)$/', $line, $m)
            && !str_contains($lines[max(0, $i - 1)], MARKER)) {
            $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
            $args   = rtrim(trim($m[2]), '),;');
            $out[] = $indent . '// ' . MARKER;
            $out[] = $indent . '// Move these constructor arguments into ' . $m[1]
                   . '::configureOptions() and pass them as form options: '
                   . ($args === '' ? '(none)' : $args);
            $sites++;
            $types[$m[1]] = true;
            $touched = true;
        }
        $out[] = $line;
    }

    if (!$touched) continue;
    $files++;
    $touchedFiles[] = realpath($f->getPathname()) ?: $f->getPathname();
    if (!$dryRun) file_put_contents($f->getPathname(), implode("\n", $out) . "\n");
}

$listPath = dirname(__DIR__) . '/contract/manual-form-files.txt';
if (!$dryRun) {
    file_put_contents($listPath, implode("\n", $touchedFiles) . (($touchedFiles) ? "\n" : ''));
}

printf("%s %d createForm() sites across %d files, %d distinct form types\n",
    $dryRun ? 'would mark' : 'marked', $sites, $files, count($types));
$names = array_keys($types);
sort($names);
echo '  ' . implode(', ', array_slice($names, 0, 8)) . (count($names) > 8 ? sprintf(', ... (+%d more)', count($names) - 8) : '') . "\n";
if (!$dryRun) echo "  file list -> migration/contract/manual-form-files.txt\n";
