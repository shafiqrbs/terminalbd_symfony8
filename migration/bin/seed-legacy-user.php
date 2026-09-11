<?php

declare(strict_types=1);

/**
 * A user the SYMFONY 2 application can actually authenticate.
 *
 * The harness needs to read legacy screens, and it cannot reach them through the
 * session bridge: that carries for a handful of paths and then legacy redirects
 * the rest to its own login or its dashboard - 1 screen in 10 served, and
 * intermittently at that.
 *
 * The reason is in `app/config/security.yml`: legacy encodes
 * FOS\UserBundle\Model\UserInterface with `sha512`, and the API issues bcrypt.
 * No user can hold both, so the fix is a user dedicated to legacy, with a
 * password legacy can verify. It deliberately CANNOT sign into the API.
 *
 * The hash is Symfony 2's MessageDigestPasswordEncoder: the salt merged as
 * "password{salt}", then sha512 over the digest and the salted value 5,000
 * times, base64 encoded. Those defaults are the encoder's, not a guess - getting
 * the iteration count or the merge format wrong produces a hash that is simply
 * never accepted, with no error to read.
 *
 * usage: php8.5 migration/bin/seed-legacy-user.php --from=migration_check_322 \
 *            --username=legacy_harvest --password=...
 */

$root = dirname(__DIR__, 2);
$options = ['from' => 'migration_check_322', 'username' => 'legacy_harvest', 'password' => null];

foreach (array_slice($argv, 1) as $arg) {
    foreach (array_keys($options) as $key) {
        if (str_starts_with($arg, "--{$key}=")) {
            $options[$key] = substr($arg, strlen($key) + 3);
        }
    }
}

if ($options['password'] === null) {
    fwrite(STDERR, "--password is required\n");
    exit(2);
}

$env = [];

foreach (file(dirname($root) . '/terminalbd_api/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!str_starts_with($line, '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[$k] = trim($v, "\"'");
    }
}

preg_match('#mysql://([^:]+):([^@]+)@([^:]+):(\d+)/([^?]+)#', $env['DATABASE_URL'], $m);
$pdo = new PDO("mysql:host={$m[3]};port={$m[4]};dbname={$m[5]}", $m[1], $m[2], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

/** Symfony 2's MessageDigestPasswordEncoder('sha512', true, 5000). */
function sha512Legacy(string $raw, string $salt): string
{
    $salted = $salt === '' ? $raw : $raw . '{' . $salt . '}';
    $digest = hash('sha512', $salted, true);

    for ($i = 1; $i < 5000; $i++) {
        $digest = hash('sha512', $digest . $salted, true);
    }

    return base64_encode($digest);
}

$source = $pdo->prepare('SELECT * FROM fos_user WHERE username = ?');
$source->execute([$options['from']]);
$row = $source->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    fwrite(STDERR, "no such user to copy from: {$options['from']}\n");
    exit(1);
}

$salt = bin2hex(random_bytes(16));
$password = sha512Legacy($options['password'], $salt);
$username = $options['username'];

$exists = $pdo->prepare('SELECT id FROM fos_user WHERE username = ?');
$exists->execute([$username]);
$id = $exists->fetchColumn();

if ($id === false) {
    $insertable = $row;
    unset($insertable['id']);
    $insertable['username'] = $username;
    $insertable['username_canonical'] = $username;
    $insertable['email'] = $username . '@example.com';
    $insertable['email_canonical'] = $username . '@example.com';
    $insertable['salt'] = $salt;
    $insertable['password'] = $password;
    $insertable['enabled'] = 1;

    $insert = $pdo->prepare(
        // Copied column for column from the source row rather than listed by
        // hand: fos_user here carries NOT NULL columns with no defaults -
        // `locked` was the first of them - and naming only the interesting ones
        // fails on whichever is added next.
        'INSERT INTO fos_user (' . implode(', ', array_keys($insertable)) . ')
         VALUES (' . implode(', ', array_fill(0, count($insertable), '?')) . ')'
    );
    $insert->execute(array_values($insertable));
    $id = (int) $pdo->lastInsertId();
    $action = 'created';
} else {
    $update = $pdo->prepare('UPDATE fos_user SET salt = ?, password = ?, roles = ?, globalOption_id = ?, enabled = 1 WHERE id = ?');
    $update->execute([$salt, $password, $row['roles'], $row['globalOption_id'], $id]);
    $action = 'updated';
}

// The profile is keyed by user_id, and a user without one is a
// `getBranches() on null` several screens later - which reads exactly like a
// port defect and is not one.
$profile = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
$profile->execute([$id]);

if ($profile->fetchColumn() === false) {
    $from = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = ?');
    $from->execute([$row['id']]);
    $template = $from->fetch(PDO::FETCH_ASSOC);

    if ($template !== false) {
        $pdo->prepare('INSERT INTO user_profiles (user_id, name, mobile, designation_id) VALUES (?, ?, ?, ?)')
            ->execute([$id, $username, $template['mobile'] ?? null, $template['designation_id'] ?? null]);
    }
}

printf("%s %s (id %d) in tenant %s, sha512 for the Symfony 2 tree\n", $action, $username, $id, $row['globalOption_id']);
