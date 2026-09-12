<?php

declare(strict_types=1);

/**
 * A verification user in a named tenant.
 *
 * The harnesses log in as somebody, and WHICH somebody turned out to decide what
 * they can see. `migration_check` was seeded in GlobalOption 1, "Terminal BD" -
 * the platform tenant. The tenant this dump is actually OF is 322, "Bengal
 * Mart", and the two do not share a row:
 *
 *     medicine_stock      tenant 1:      0   tenant 322: 16,571
 *     account_sales       tenant 1:      0   tenant 322:  2,118
 *     AccountCash         tenant 1:      0   tenant 322:  2,168
 *     AccountingConfig    tenant 1:   none   tenant 322:  id 1
 *
 * So every comparison run before 2026-09-10 was made against the platform
 * tenant's scraps, and agreed at zero on the screens that matter most. The fix
 * is not another dump - it is logging in as somebody who owns the data.
 *
 * This seeds such a user, mirroring `migration_check`: ROLE_SUPER_ADMIN, an
 * empty salt, and a profile row.
 *
 *   php8.5 migration/bin/seed-harness-user.php --tenant=322 --username=migration_check_322 \
 *       --roles-from=siddhartha --roles-from-nav
 *
 * The roles matter more than they look. **ROLE_SUPER_ADMIN is not a superset of
 * the domain roles**: the hierarchy grants a super admin 148 roles and
 * ROLE_DOMAIN_ACCOUNTING_SALES_REPORT is not among them, so six Accounting
 * screens answered 403 for a super admin and 200 for the tenant's own operator.
 *
 * `--roles-from` copies a real user's roles, which is the honest way to see what
 * that person sees. `--roles-from-nav` adds every role the NAVIGATION INVENTORY
 * names - 334 of 335 screens carry a guard - so a harness user can reach every
 * screen there is. That is what a comparison harness needs and what a real
 * account should never have: the API still enforces each guard per request, this
 * user simply satisfies all of them.
 *
 * Without it, phase 6 opened with 34 screens answering 403 because no user in
 * this tenant holds ROLE_BUSINESS_REPORT or ROLE_DOMAIN_HOSPITAL_MASTERDATA -
 * which says nothing about the port, only about who was asking.
 *
 * Two things it must get right, both learned the hard way:
 *
 *   - `fos_user.salt` is NOT NULL and Symfony 8's bcrypt wants no salt at all,
 *     so it is written as the empty string rather than left null.
 *   - a user needs a row in `user_profiles`. `migration_check` was the only one
 *     of 1,088 users without one, and a great deal of this code calls
 *     `$this->getUser()->getProfile()->...` unguarded - so a missing profile is
 *     reported by the harnesses as if it were a port defect.
 *
 * The password is read from next/.env.local's SMOKE_TEST_PASSWORD, so every
 * harness user in this database shares one, and nothing new has to be recorded.
 */

$root = dirname(__DIR__, 2);
$tenant = null;
$username = null;

$rolesFrom = null;
$rolesFromNav = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--tenant=')) {
        $tenant = (int) substr($arg, 9);
    } elseif (str_starts_with($arg, '--username=')) {
        $username = substr($arg, 11);
    } elseif (str_starts_with($arg, '--roles-from=')) {
        $rolesFrom = substr($arg, 13);
    } elseif ($arg === '--roles-from-nav') {
        $rolesFromNav = true;
    }
}

if ($tenant === null || $username === null) {
    fwrite(STDERR, "usage: seed-harness-user.php --tenant=322 --username=migration_check_322\n");

    exit(2);
}

$env = [];

foreach (file($root . '/next/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!str_starts_with($line, '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[$k] = trim($v, "\"'");
    }
}

$password = $env['SMOKE_TEST_PASSWORD'] ?? '';

if ($password === '') {
    fwrite(STDERR, "next/.env.local has no SMOKE_TEST_PASSWORD\n");

    exit(2);
}

$url = parse_url($env['DATABASE_URL'] ?? '');
$db = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $url['host'], $url['port'] ?? 3306, ltrim($url['path'], '/')),
    $url['user'],
    urldecode($url['pass'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$exists = $db->prepare('SELECT id FROM GlobalOption WHERE id = ?');
$exists->execute([$tenant]);

if ($exists->fetchColumn() === false) {
    fwrite(STDERR, sprintf("no tenant %d in this database\n", $tenant));

    exit(1);
}

$find = $db->prepare('SELECT id FROM fos_user WHERE username = ?');
$find->execute([$username]);
$id = $find->fetchColumn();

// Cost 13, which is what the application's hasher is configured with - a
// mismatch here would still authenticate, and would rewrite the hash on first
// login, which is a change to the fixture nobody asked for.
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);
$granted = ['ROLE_SUPER_ADMIN'];

if ($rolesFrom !== null) {
    $source = $db->prepare('SELECT roles FROM fos_user WHERE username = ?');
    $source->execute([$rolesFrom]);
    $found = $source->fetchColumn();

    if ($found === false) {
        fwrite(STDERR, sprintf("no user %s to copy roles from\n", $rolesFrom));

        exit(1);
    }

    $copied = unserialize((string) $found, ['allowed_classes' => false]);
    $granted = array_values(array_unique([...$granted, ...(is_array($copied) ? $copied : [])]));
}

if ($rolesFromNav) {
    // The navigation inventory moved to the API repository with the rest of the
    // API/React port. It is still generated from THIS tree, and it is still the
    // only list of every role the menu names - which is what --roles-from-nav
    // grants, so that a harness sweep is never stopped by a 403 it invented.
    $navScope = getenv('NAV_SCOPE')
        ?: dirname($root) . '/terminalbd_api/docs/contract/nav-scope.json';

    if (!is_file($navScope)) {
        fwrite(STDERR, sprintf(
            "--roles-from-nav needs the navigation inventory, and there is none at %s.\n\n"
            . "It lives in the API repository now. Check terminalbd_api out beside this\n"
            . "one, or point NAV_SCOPE at its docs/contract/nav-scope.json. Regenerate it\n"
            . "with: php8.5 tools/nav-inventory.php --legacy-root=%s\n",
            $navScope,
            $root,
        ));

        exit(2);
    }

    $scope = json_decode((string) file_get_contents($navScope), true);

    foreach ($scope['modules'] ?? [] as $screens) {
        foreach ($screens as $screen) {
            foreach ($screen['roles'] ?? [] as $choice) {
                foreach ($choice as $role) {
                    $granted[] = $role;
                }
            }
        }
    }

    $granted = array_values(array_unique($granted));
}

$roles = serialize($granted);

if ($id === false) {
    $db->prepare(
        'INSERT INTO fos_user (username, username_canonical, email, email_canonical, enabled, salt,
            password, roles, locked, expired, credentials_expired, globalOption_id, isDelete, domainOwner)
         VALUES (?, ?, ?, ?, 1, \'\', ?, ?, 0, 0, 0, ?, 0, 1)'
    )->execute([
        $username,
        $username,
        $username . '@example.com',
        $username . '@example.com',
        $hash,
        $roles,
        $tenant,
    ]);
    $id = (int) $db->lastInsertId();
    printf("created user %d\n", $id);
} else {
    $id = (int) $id;
    $db->prepare('UPDATE fos_user SET password = ?, roles = ?, enabled = 1, salt = \'\', globalOption_id = ? WHERE id = ?')
        ->execute([$hash, $roles, $tenant, $id]);
    printf("updated user %d\n", $id);
}

// user_id, NOT id. The profile has a primary key of its own and points at the
// user through a foreign key; writing the user's id into `id` produces a row
// that looks seeded and that getProfile() never finds, which surfaces later as
// `getBranches() on null` in whichever screen reads it first.
$profile = $db->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
$profile->execute([$id]);

if ($profile->fetchColumn() === false) {
    $db->prepare('INSERT INTO user_profiles (user_id, name) VALUES (?, ?)')->execute([$id, $username]);
    echo "created profile\n";
}

printf(
    "%s is user %d in tenant %d (%s), %d role(s)\n",
    $username,
    $id,
    $tenant,
    $db->query(sprintf('SELECT name FROM GlobalOption WHERE id = %d', $tenant))->fetchColumn() ?: '-',
    count($granted)
);
