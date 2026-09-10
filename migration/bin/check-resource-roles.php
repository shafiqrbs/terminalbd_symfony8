<?php

declare(strict_types=1);

/**
 * Every API resource guards its screen exactly as the navigation says.
 *
 * The nav inventory records a guard per screen - 334 of 335 have one - as an AND
 * of ORs, and `ResourceController::guard()` enforces the copy held on each
 * resource. Two copies of the same fact drift, and this one drifts SILENTLY: the
 * harness signs in as a user holding every role there is, so a resource that
 * guards too loosely answers 200 there and would answer 200 for anyone.
 *
 * Phase 7 is why this exists. Three Human Resource screens were written with
 * `[['ROLE_HR','ROLE_PAYROLL']]` where the menu also requires ROLE_HR_ATTENDANCE
 * or ROLE_PAYROLL_SALARY, and both POS screens were given a role - ROLE_DOMAIN_POS
 * - that appears nowhere in the application. The 403s found the second mistake;
 * nothing would have found the first.
 *
 * A resource may be STRICTER than the menu without failing here: the menu decides
 * what to show and the API decides what is allowed, and the API is entitled to
 * refuse something the menu offered. Looser is the defect.
 *
 * **What this does NOT cover**: the six screens served by a bespoke controller
 * rather than by a resource - Inventory's four and Accounting's two - keep their
 * guard as a class constant, out of reach of the registry. They are guarded, and
 * they are not checked here.
 *
 * usage: php8.5 migration/bin/check-resource-roles.php [--api=../terminalbd_api]
 */

$root = dirname(__DIR__, 2);
$api = $root . '/../terminalbd_api';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--api=')) {
        $api = substr($arg, 6);
    }
}

if (!is_file($api . '/vendor/autoload.php')) {
    printf("skipped: no API checkout at %s\n", $api);

    exit(0);
}

require $api . '/vendor/autoload.php';

$scope = json_decode(file_get_contents($root . '/migration/contract/nav-scope.json'), true);
$want = [];

foreach ($scope['modules'] as $screens) {
    foreach ($screens as $screen) {
        $want[$screen['route']] = $screen['roles'] ?? [];
    }
}

/** An AND-of-ORs as a comparable string, order-insensitive within each choice. */
$shape = static function (array $roles): string {
    $choices = array_map(static function (array $choice): string {
        sort($choice);

        return implode('|', $choice);
    }, $roles);
    sort($choices);

    return implode(' AND ', $choices);
};

$looser = [];
$stricter = [];
$checked = 0;

foreach (App\Api\Resource\ResourceRegistry::all() as $key => $resource) {
    if (!isset($want[$key])) {
        continue;
    }

    $checked++;
    $mine = $shape($resource->roles);
    $theirs = $shape($want[$key]);

    if ($mine === $theirs) {
        continue;
    }

    // Looser means the resource omits a choice the menu requires, so somebody
    // the menu would not have shown the screen to can call it.
    $missing = array_diff(
        array_map($shape, array_map(static fn (array $c): array => [$c], $want[$key])),
        array_map($shape, array_map(static fn (array $c): array => [$c], $resource->roles))
    );

    if ($missing !== []) {
        $looser[] = sprintf("%s\n      nav: %s\n      api: %s", $key, $theirs, $mine);
    } else {
        $stricter[] = sprintf('%s (api is stricter: %s)', $key, $mine);
    }
}

printf("%d resources checked against the navigation inventory\n", $checked);

if ($stricter !== []) {
    printf("\n%d stricter than the menu, which is allowed:\n", count($stricter));

    foreach ($stricter as $line) {
        echo '  ' . $line . "\n";
    }
}

if ($looser === []) {
    echo "\nno resource guards more loosely than its menu entry\n";

    exit(0);
}

printf("\n%d resource(s) guard MORE LOOSELY than the menu:\n", count($looser));

foreach ($looser as $line) {
    echo '  ' . $line . "\n";
}

exit(1);
