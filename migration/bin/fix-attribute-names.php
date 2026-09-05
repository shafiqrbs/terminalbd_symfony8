<?php

declare(strict_types=1);

/**
 * Two attribute-resolution defects the annotation-to-attribute conversion left.
 *
 * 1. `#[Doctrine\ORM\Mapping\Column]`. In a docblock that FQCN was absolute;
 *    as an attribute PHP resolves it relative to the current namespace, so it
 *    becomes App\<Module>\Entity\Doctrine\ORM\Mapping\Column - a class that
 *    does not exist. Doctrine then sees no Column at all and the property is
 *    silently unmapped. 32 columns, including HmsCategory::$slug, whose
 *    absence makes Gedmo fail with "Unable to find slug [slug] as mapped
 *    property".
 *
 * 2. `targetEntity: \Foo::class`. ::class does not require the class to exist,
 *    so this quietly evaluates to "Foo" and Doctrine cannot resolve it. The
 *    annotation meant a class in the entity's own namespace.
 *
 * Idempotent. Usage: php8.5 migration/bin/fix-attribute-names.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

// class short name -> FQCN, for every class under src/
$index = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $code = file_get_contents($file->getPathname());

    if (preg_match('/^namespace\s+([^;]+);/m', $code, $ns)
        && preg_match('/^(?:final |abstract )?class\s+(\w+)/m', $code, $cm)) {
        $index[$cm[1]][] = trim($ns[1]) . '\\' . $cm[1];
    }
}

$stats = ['attribute names' => 0, 'targetEntity' => 0, 'ambiguous' => 0, 'files' => 0];
$ambiguous = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;

    preg_match('/^namespace\s+([^;]+);/m', $code, $ns);
    $namespace = trim($ns[1] ?? '');

    // 1. #[Doctrine\ORM\Mapping\X] -> #[ORM\X], #[Gedmo\Mapping\Annotation\X] -> #[Gedmo\X]
    $code = preg_replace('/#\[Doctrine\\\\ORM\\\\Mapping\\\\/', '#[ORM\\', $code, -1, $n);
    $stats['attribute names'] += $n;

    $code = preg_replace('/#\[Gedmo\\\\Mapping\\\\Annotation\\\\/', '#[Gedmo\\', $code, -1, $n);
    $stats['attribute names'] += $n;

    if ($n > 0 || str_contains($code, '#[ORM\\')) {
        if (!preg_match('/^use\s+Doctrine\\\\ORM\\\\Mapping\s+as\s+ORM;/m', $code) && str_contains($code, '#[ORM\\')) {
            $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse Doctrine\\ORM\\Mapping as ORM;\n", $code, 1);
        }
    }

    // 2. targetEntity: \Foo::class -> the class the annotation meant
    $code = preg_replace_callback(
        '/targetEntity:\s*\\\\(\w+)::class/',
        static function (array $m) use ($index, $namespace, &$stats, &$ambiguous, $path, $root): string {
            $short = $m[1];
            $candidates = $index[$short] ?? [];

            // The annotation was relative to the entity's own namespace.
            $own = $namespace . '\\' . $short;

            if (in_array($own, $candidates, true)) {
                ++$stats['targetEntity'];

                return 'targetEntity: ' . $short . '::class';
            }

            if (count($candidates) === 1) {
                ++$stats['targetEntity'];

                return 'targetEntity: \\' . $candidates[0] . '::class';
            }

            ++$stats['ambiguous'];
            $ambiguous[] = str_replace($root . '/', '', $path) . ': \\' . $short . '::class ('
                . (count($candidates) === 0 ? 'no such class' : count($candidates) . ' candidates') . ')';

            return $m[0];
        },
        $code,
    );

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-18s %d\n", $label, $count);
}

foreach ($ambiguous as $line) {
    echo "  {$line}\n";
}
