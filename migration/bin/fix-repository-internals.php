<?php

declare(strict_types=1);

/**
 * Doctrine ORM 3 removed EntityRepository's protected $_em.
 *
 * It is `private readonly EntityManagerInterface $em` now, reachable only
 * through the protected getEntityManager(). The 229 ported repositories use
 * `$this->_em` 2,056 times, and every one of those reads an undefined property:
 * PHP emits a warning and evaluates to null, so the next call - almost always
 * ->createQuery() or ->getRepository() - is a fatal error.
 *
 * Nothing static catches it. The class compiles, the container lints, the
 * mapping validates; the query dies when someone opens the page.
 *
 * Idempotent. Usage: php8.5 migration/bin/fix-repository-internals.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

/** Removed property => the accessor ORM 3 provides. */
const REPLACEMENTS = [
    '_em' => 'getEntityManager()',
    '_entityName' => 'getEntityName()',
    '_class' => 'getClassMetadata()',
];

$stats = ['uses' => 0, 'files' => 0];
$outsideRepositories = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!preg_match('/\$this->(_(?:em|entityName|class)\b|getDoctrine\()/', $code)) {
        continue;
    }

    // Only a repository inherits those accessors; anywhere else this would
    // rename a property the class declares itself. Gedmo's tree repositories
    // extend EntityRepository too, under their own names.
    if (!preg_match('/class\s+\w+\s+extends\s+(\w*EntityRepository|NestedTreeRepository|MaterializedPathRepository|ClosureTreeRepository|AbstractTreeRepository)\b/', $code)) {
        $outsideRepositories[] = substr($path, strlen($root) + 1);
        continue;
    }

    $original = $code;

    // A repository has no getDoctrine(): the legacy code borrowed the
    // controller idiom. getEntityManager() is the same thing here.
    $code = preg_replace('/\$this->getDoctrine\(\)->getManager\(\)/', '$this->getEntityManager()', $code, -1, $n);
    $code = preg_replace('/\$this->getDoctrine\(\)/', '$this->getEntityManager()', $code, -1, $m);
    $stats['uses'] += $n + $m;

    foreach (REPLACEMENTS as $property => $accessor) {
        $code = preg_replace(
            '/\$this->' . $property . '\b(?!\s*=[^=])/',
            '$this->' . $accessor,
            $code,
            -1,
            $n,
        );
        $stats['uses'] += $n;
    }

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("removed repository internals rewritten: %d uses in %d files\n", $stats['uses'], $stats['files']);

foreach ($outsideRepositories as $path) {
    printf("  left alone (not an EntityRepository): %s\n", $path);
}
