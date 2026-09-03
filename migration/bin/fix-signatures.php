<?php
/**
 * Adds the return types Symfony 8 / Doctrine 3 base classes now declare.
 *
 * Symfony 2 left these untyped; a subclass that omits the type is a fatal
 * ("must be compatible with ..."). Rector covers most, but not the 172 files it
 * skips because of non-mechanical form instantiation.
 *
 * Signatures are only changed when the class actually extends or implements the
 * type that declares them. Matching on method name alone is not safe: 24
 * entities have their own getParent() returning a related entity, and typing
 * those ": ?string" is a runtime TypeError that no compile step would catch.
 *
 * usage: php migration/bin/fix-signatures.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: fix-signatures.php <dir> [--dry-run]\n"); exit(1); }

/** context marker (matched against extends/implements) => [method => return type] */
$CONTEXTS = [
    'AbstractType' => [
        'buildForm' => 'void', 'buildView' => 'void', 'finishView' => 'void',
        'configureOptions' => 'void', 'getBlockPrefix' => 'string', 'getParent' => '?string',
    ],
    'FormTypeInterface' => [
        'buildForm' => 'void', 'buildView' => 'void', 'finishView' => 'void',
        'configureOptions' => 'void', 'getBlockPrefix' => 'string', 'getParent' => '?string',
    ],
    'AbstractTypeExtension' => ['getExtendedTypes' => 'iterable', 'configureOptions' => 'void'],
    'EventSubscriberInterface' => ['getSubscribedEvents' => 'array'],
    'AbstractExtension' => [
        'getFilters' => 'array', 'getFunctions' => 'array',
        'getTests' => 'array', 'getOperators' => 'array',
    ],
    'FunctionNode' => ['getSql' => 'string', 'parse' => 'void'],
    'DataTransformerInterface' => [],
];

/**
 * Type-hints that are no longer autowirable services.
 * DoctrineBundle 3 registers Doctrine\Persistence\ManagerRegistry, not its own
 * Registry subclass, so a constructor asking for the latter cannot be built.
 */
$TYPE_RENAMES = [
    'Doctrine\\Bundle\\DoctrineBundle\\Registry' => 'Doctrine\\Persistence\\ManagerRegistry',
];

$renamed = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = (string) file_get_contents($f->getPathname());
    $out = $src;
    foreach ($TYPE_RENAMES as $from => $to) {
        $out = str_replace($from, $to, $out);
        $shortFrom = substr($from, strrpos($from, '\\') + 1);
        $shortTo   = substr($to, strrpos($to, '\\') + 1);
        if ($out !== $src) {
            $out = preg_replace('/\b' . preg_quote($shortFrom, '/') . '\s+(\$\w+)/', $shortTo . ' $1', $out);
        }
    }
    if ($out === $src) continue;
    $renamed++;
    if (!$dryRun) file_put_contents($f->getPathname(), $out);
}
printf("%s %d files with non-autowirable type hints\n", $dryRun ? 'would rename in' : 'renamed in', $renamed);

$fixed = 0; $files = 0; $byMethod = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = (string) file_get_contents($f->getPathname());

    // the class's own declaration line - what it extends / implements
    if (!preg_match('/^\s*(?:final\s+|abstract\s+)?class\s+\w+([^{]*)\{/m', $src, $decl)) continue;
    $bases = $decl[1];

    $methods = [];
    foreach ($CONTEXTS as $marker => $map) {
        if (preg_match('/\b' . preg_quote($marker, '/') . '\b/', $bases)) {
            $methods += $map;
        }
    }
    if (!$methods) continue;

    $out = $src;
    foreach ($methods as $method => $type) {
        $out = preg_replace_callback(
            '/(\bpublic\s+function\s+' . preg_quote($method, '/') . '\s*\([^{;]*\))(\s*)(\{|;)/',
            static function (array $m) use ($type, $method, &$fixed, &$byMethod): string {
                if (preg_match('/\)\s*:\s*\S+\s*$/', $m[1])) return $m[0];   // already typed
                $fixed++;
                $byMethod[$method] = ($byMethod[$method] ?? 0) + 1;
                return $m[1] . ': ' . $type . $m[2] . $m[3];
            },
            $out
        );
    }

    if ($out === $src) continue;
    $files++;
    if (!$dryRun) file_put_contents($f->getPathname(), $out);
}

printf("%s %d method signatures across %d files\n", $dryRun ? 'would fix' : 'fixed', $fixed, $files);
arsort($byMethod);
foreach ($byMethod as $m => $n) printf("  %-22s %d\n", $m, $n);
