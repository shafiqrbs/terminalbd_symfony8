<?php

declare(strict_types=1);

/**
 * Builds the per-endpoint request recipes the golden capture needs.
 *
 * Without them the capture is nearly worthless: the mobile API reads its input
 * out of $request->request->all() / $_REQUEST, so an endpoint called with no
 * body returns 500 on both applications and a matching pair of 500s proves
 * nothing about the port. Filling the bodies is what moves an endpoint from
 * "both crash" to "both answer, and here is whether the answer has the same
 * shape" - which is where wire drift actually lives.
 *
 * The keys are read out of the LEGACY action body, because the legacy tree is
 * the contract. Values come from the scratch database wherever the key names a
 * real row, so the request looks like one the mobile client would send. The
 * result is written into capture-config.local.json, which is gitignored: the
 * values are this tenant's data.
 *
 * usage: php8.5 migration/bin/api-recipes.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

$endpoints = json_decode((string) file_get_contents("$root/migration/contract/api-endpoints.json"), true);

/* ------------------------------------------------------- the legacy actions */

/** Bundle:Controller -> the legacy controller file. */
$controllerFile = static function (string $controller) use ($root): ?string {
    [$bundle, $class] = explode(':', $controller);

    foreach (glob("$root/src/*/*/Controller/{$class}Controller.php") ?: [] as $f) {
        return $f;
    }
    foreach (glob("$root/src/*/*/*/Controller/{$class}Controller.php") ?: [] as $f) {
        return $f;
    }

    return null;
};

/** The body of one action, from its signature to the next one. */
$actionBody = static function (string $file, string $action): ?string {
    static $cache = [];
    $src = $cache[$file] ??= (string) file_get_contents($file);

    if (!preg_match('/function\s+' . preg_quote($action, '/') . 'Action\s*\(/i', $src, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $start = $m[0][1];
    $next = preg_match('/\n    (?:public|private|protected)\s+function\s/', $src, $mm, PREG_OFFSET_CAPTURE, $start + 1)
        ? $mm[0][1]
        : strlen($src);

    return substr($src, $start, $next - $start);
};

/* ------------------------------------------------- values for the key names */

$env = [];
foreach (file("$root/next/.env.local") as $line) {
    if (preg_match('/^([A-Z_]+)="?([^"\n]*)"?/', trim($line), $m)) {
        $env[$m[1]] = $m[2];
    }
}
preg_match('#mysql://([^:]+):([^@]*)@([^:/]+):(\d+)/([^?]+)#', $env['DATABASE_URL'] ?? '', $m)
    or exit("could not parse DATABASE_URL from next/.env.local\n");
[, $dbUser, $dbPass, $dbHost, $dbPort, $dbName] = $m;
$pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

/** First scalar of a query, or null when the table/column is not in this dump. */
$one = static function (string $sql) use ($pdo) {
    try {
        $v = $pdo->query($sql)->fetchColumn();

        return $v === false ? null : $v;
    } catch (PDOException) {
        return null;
    }
};

$tenant = $pdo->query('SELECT id, uniqueCode, mobile FROM `GlobalOption` WHERE status = 1 AND uniqueCode IS NOT NULL ORDER BY id')->fetch(PDO::FETCH_ASSOC)
    ?: exit("no tenant with a uniqueCode in the scratch database\n");

$today = date('Y-m-d');
$monthAgo = date('Y-m-d', strtotime('-1 month'));

// Key name -> value. Ordered: the first pattern that matches a key wins, so
// the specific names come before the generic id/name catch-alls.
$rules = [
    '/^uniqueCode$|^apiSecret$/'          => $tenant['uniqueCode'],
    '/^deviceId$|^device_id$/'            => (string) ($one("SELECT deviceId FROM AndroidDeviceSetup WHERE deviceId IS NOT NULL AND deviceId <> '' LIMIT 1") ?? 'golden-capture-device'),
    '/date$|^date|^month$|^year$/i'       => null, // handled below, needs direction
    '/^mobile$|phone/i'                   => (string) ($tenant['mobile'] ?: '01700000000'),
    '/^email$/i'                          => (string) ($one("SELECT email FROM fos_user WHERE email IS NOT NULL AND email <> '' LIMIT 1") ?? 'capture@example.test'),
    '/^username$|^user_name$/i'           => (string) ($one("SELECT username FROM fos_user WHERE username IS NOT NULL LIMIT 1") ?? 'migration_check'),
    '/^password$|^otp$/i'                 => 'golden-capture-not-a-real-secret',
    '/^user_?id$|^userId$/i'              => (string) ($one("SELECT id FROM fos_user ORDER BY id LIMIT 1") ?? '1'),
    '/^user_role$|^role$/i'               => 'ROLE_DOMAIN',
    '/^global_?id$|^globalOption$/i'      => (string) $tenant['id'],
    '/^invoice$/i'                        => (string) ($one("SELECT invoice FROM Sales WHERE invoice IS NOT NULL LIMIT 1") ?? 'INV-1'),
    '/^method_?id$/i'                     => (string) ($one("SELECT id FROM TransactionMethod ORDER BY id LIMIT 1") ?? '1'),
    '/^vendor_?id$/i'                     => (string) ($one("SELECT id FROM Vendor ORDER BY id LIMIT 1") ?? '1'),
    '/^customer_?id$/i'                   => (string) ($one("SELECT id FROM Customer ORDER BY id LIMIT 1") ?? '1'),
    '/^item_?id$|^stock_?id$|^stockId$/i' => (string) ($one("SELECT id FROM StockItem ORDER BY id LIMIT 1") ?? '1'),
    '/^category_?id$/i'                   => (string) ($one("SELECT id FROM Category ORDER BY id LIMIT 1") ?? '1'),
    '/^mode$/i'                           => 'online',
    '/^process$|^status$/i'               => 'Done',
    '/^name$|^address$|^comment$|^message$|^remark/i' => 'golden capture',
    '/^id$/i'                             => (string) $tenant['id'],
    '/quantity|price|amount|total|discount|vat|received/i' => '1',
];

$valueFor = static function (string $key) use ($rules, $today, $monthAgo): string {
    // Dates carry a direction the name gives away, so they are resolved first.
    if (preg_match('/^(start|from)/i', $key) && preg_match('/date/i', $key)) {
        return $monthAgo;
    }
    if (preg_match('/date$|^date/i', $key)) {
        return $today;
    }

    foreach ($rules as $pattern => $value) {
        if ($value !== null && preg_match($pattern, $key)) {
            return (string) $value;
        }
    }

    return '1';
};

/* --------------------------------------------------------- build the recipes */

$recipes = [];
$missing = [];
$noKeys = $noAction = 0;

foreach ($endpoints as $r) {
    $key = $r['prefix'] . $r['path'];
    [$bundle, $class, $action] = explode(':', $r['controller']);

    $file = $controllerFile("$bundle:$class");
    $body = $file === null ? null : $actionBody($file, $action);

    if ($body === null) {
        $noAction++;
        $missing[] = $r['controller'];
        continue;
    }

    // Every way these actions name an input field.
    $keys = [];
    if (preg_match_all('/\$(?:data|formData|request_data|post)\s*\[\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\]/', $body, $m)) {
        $keys = array_merge($keys, $m[1]);
    }
    if (preg_match_all('/\$request->(?:request|query)->get\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $body, $m)) {
        $keys = array_merge($keys, $m[1]);
    }
    if (preg_match_all('/\$_REQUEST\s*\[\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\]/', $body, $m)) {
        $keys = array_merge($keys, $m[1]);
    }

    $keys = array_values(array_unique($keys));

    // Path placeholders are filled from the same rules.
    $pathParams = [];
    if (preg_match_all('/\{([^}]+)\}/', $r['path'], $m)) {
        foreach ($m[1] as $p) {
            $pathParams[$p] = $valueFor($p);
        }
    }

    if ($keys === [] && $pathParams === []) {
        $noKeys++;
        continue;
    }

    $values = [];
    foreach ($keys as $k) {
        $values[$k] = $valueFor($k);
    }

    // The verb follows the input source, not the route: these routes declare no
    // `methods:` at all, so every one of them accepts any verb. An action
    // reading $request->request->all() or $formData sees only a POST body, and
    // called as GET it gets an empty array however well-formed the query is.
    $needsPost = (bool) preg_match('/\$request->request->|\$formData\s*\[|\$formData\s*=/', $body);

    $recipe = [];
    if ($pathParams !== []) {
        $recipe['path_params'] = $pathParams;
    }
    if ($values !== []) {
        $recipe['method'] = $needsPost ? 'POST' : strtoupper($r['methods'][0]);
        $recipe[$needsPost ? 'body' : 'query'] = $values;
    }

    $recipes[$key] = $recipe;
}

printf(
    "endpoints: %d\n  recipes built:      %d\n  action not found:   %d\n  reads no input:     %d\n",
    count($endpoints),
    count($recipes),
    $noAction,
    $noKeys
);

if ($missing !== []) {
    printf("  actions not found:  %s\n", implode(', ', array_unique($missing)));
}

if ($dryRun) {
    foreach (array_slice($recipes, 0, 8, true) as $k => $v) {
        printf("  %-46s %s\n", $k, json_encode($v));
    }
    exit(0);
}

$localPath = "$root/migration/capture-config.local.json";
$local = is_file($localPath) ? json_decode((string) file_get_contents($localPath), true) : [];
$local['per_endpoint'] = $recipes;
file_put_contents($localPath, json_encode($local, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

printf("wrote %d recipes into migration/capture-config.local.json\n", count($recipes));
