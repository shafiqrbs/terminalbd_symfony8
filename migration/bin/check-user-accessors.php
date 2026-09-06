<?php

declare(strict_types=1);

/**
 * Checks that every `app.user.*` a template reads resolves on the User entity.
 *
 * App\Security\Model\BaseUser is hand-written - it stands in for
 * FOS\UserBundle\Model\User, which has no Symfony 5+ release. A method left out
 * of it does not fail anywhere near the omission: it fails as a Twig error on
 * whichever page happens to read that property. `getId()` was missing that way,
 * which took out /backend, /user-admin and /admin/domain - three of the first
 * pages anyone opens - while the container, the router and the mapping all
 * reported themselves healthy.
 *
 * Only `app.user.` is checked, because that is the one expression whose type is
 * known without inference. It is a narrow guard for a specific hand-written
 * class, not a general template checker.
 *
 * usage: php migration/bin/check-user-accessors.php
 */
require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

// Present in the legacy templates and broken in the legacy application too:
// Core\UserBundle\Entity\User never had getName() either, so this is an
// application bug that predates the migration, not something the port dropped.
const KNOWN_LEGACY_BUGS = [
    'name' => 'User/Registration/confirmed.html.twig - no getName() in either tree',
];

$templates = dirname(__DIR__, 2) . '/next/templates';
$reflection = new ReflectionClass(App\User\Entity\User::class);

$resolves = static function (string $property) use ($reflection): bool {
    foreach ([$property, "get{$property}", "is{$property}", "has{$property}"] as $candidate) {
        if ($reflection->hasMethod($candidate) && $reflection->getMethod($candidate)->isPublic()) {
            return true;
        }
    }

    return $reflection->hasProperty($property)
        && $reflection->getProperty($property)->isPublic();
};

$used = [];

foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templates, FilesystemIterator::SKIP_DOTS)
) as $file) {
    if ($file->getExtension() !== 'twig') {
        continue;
    }

    $source = (string) file_get_contents($file->getPathname());

    if (!preg_match_all('/\bapp\.user\.(\w+)/', $source, $matches)) {
        continue;
    }

    foreach ($matches[1] as $property) {
        $used[$property][] = str_replace($templates . '/', '', $file->getPathname());
    }
}

ksort($used);

$missing = [];

foreach ($used as $property => $files) {
    if ($resolves($property) || isset(KNOWN_LEGACY_BUGS[$property])) {
        continue;
    }

    $missing[$property] = $files;
}

printf(
    "app.user.* accessors: %d distinct, %d unresolvable, %d known legacy bug(s)\n",
    count($used),
    count($missing),
    count(KNOWN_LEGACY_BUGS)
);

foreach ($missing as $property => $files) {
    printf("    app.user.%-20s %d use(s), e.g. %s\n", $property, count($files), $files[0]);
}

exit($missing === [] ? 0 : 1);
