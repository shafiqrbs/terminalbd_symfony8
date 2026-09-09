<?php
/**
 * Records golden responses from an app so the SF2.8 -> SF8 port can be proven
 * wire-compatible. Run once against the legacy app, once against the new one,
 * then diff with compare.php.
 *
 * Real values - a base URL, the API key pair, a tenant's X-API-SECRET - go in
 * migration/capture-config.local.json, which is gitignored and merged over the
 * tracked capture-config.json. Nothing secret belongs in the tracked file.
 *
 * What is written is the response *shape*: keys and value types, plus the
 * status. That is what compare.php checks and it is schema rather than tenant
 * data, so the fixtures are safe to commit. --with-body adds the decoded body
 * for local debugging, and is why migration/fixtures/ is gitignored.
 *
 * --read-only skips every endpoint that might write, on the same deny-list
 * smoke-test.php uses. It matters here more than there: both apps share one
 * database, so a write during the legacy capture changes what the next capture
 * sees, and the diff then reports the harness rather than the port.
 *
 * usage: php migration/bin/capture.php <legacy|next> [--only=substring] [--with-body] [--read-only]
 */
$target = $argv[1] ?? null;
if (!in_array($target, ['legacy', 'next'], true)) {
    fwrite(STDERR, "usage: capture.php <legacy|next> [--only=substring]\n");
    exit(1);
}
$only = null;
$withBody = false;
$readOnly = false;
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--only=')) $only = substr($a, 7);
    if ($a === '--with-body') $withBody = true;
    if ($a === '--read-only') $readOnly = true;
}

/**
 * Anything that might write - copied from smoke-test.php, where the same list
 * keeps a legacy app that deletes over GET from doing so.
 */
const UNSAFE = [
    'delete', 'remove', 'destroy', 'truncate', 'drop', 'purge', 'clear',
    'create', 'new', 'add', 'insert', 'save', 'store', 'update', 'edit',
    'approve', 'reject', 'confirm', 'cancel', 'reverse', 'revert', 'reset',
    'install', 'migrate', 'import', 'upload', 'send', 'sms', 'mail', 'email',
    'pay', 'payment', 'invoice-process', 'generate', 'sync', 'switch', 'login',
    'logout', 'register', 'signup', 'password', 'process', 'status', 'active',
    'inactive', 'publish', 'copy', 'clone', 'merge', 'assign', 'apply', 'post',
];

$root      = dirname(__DIR__, 2);
$endpoints = json_decode(file_get_contents("$root/migration/contract/api-endpoints.json"), true);
$cfg       = json_decode(file_get_contents("$root/migration/capture-config.json"), true);

// The gitignored overlay wins, key by key, so the tracked file keeps its
// placeholders and the real values never enter git.
$localCfg = "$root/migration/capture-config.local.json";
if (is_file($localCfg)) {
    $local = json_decode((string) file_get_contents($localCfg), true);
    if (!is_array($local)) {
        fwrite(STDERR, "capture-config.local.json is not valid JSON\n");
        exit(1);
    }
    foreach ($local as $k => $v) {
        $cfg[$k] = is_array($v) && is_array($cfg[$k] ?? null) ? array_merge($cfg[$k], $v) : $v;
    }
    fwrite(STDERR, "merged migration/capture-config.local.json\n");
}
$outDir    = "$root/migration/fixtures/$target";

// base_url is either one string or a map keyed by target, because the two apps
// are served side by side on different ports.
$base = is_array($cfg['base_url']) ? ($cfg['base_url'][$target] ?? '') : $cfg['base_url'];
$base = rtrim((string) $base, '/');
if ($base === '' || str_contains($base, 'CHANGE-ME')) {
    fwrite(STDERR, "Set base_url for '$target' in migration/capture-config.local.json first.\n");
    exit(1);
}

$headers = array_filter($cfg['headers'] ?? []);
$ok = $fail = $skipped = 0;

foreach ($endpoints as $r) {
    $key = $r['prefix'] . $r['path'];
    if ($only !== null && !str_contains($key, $only)) continue;
    if (in_array($key, $cfg['skip'] ?? [], true)) { $skipped++; continue; }

    if ($readOnly) {
        $haystack = strtolower($r['name'] . ' ' . $key);
        $writes = strtoupper($r['methods'][0]) !== 'GET';
        foreach (UNSAFE as $word) {
            if (str_contains($haystack, $word)) { $writes = true; break; }
        }
        if ($writes) { $skipped++; continue; }
    }

    // Routes with placeholders need explicit values before they can be called.
    if (preg_match('/\{[^}]+\}/', $r['path'])) {
        $recipe = $cfg['per_endpoint'][$key] ?? null;
        if (!isset($recipe['path_params'])) {
            fwrite(STDERR, "SKIP (needs path_params): $key\n");
            $skipped++;
            continue;
        }
    }

    $method = strtoupper($r['methods'][0]);
    $recipe = $cfg['per_endpoint'][$key] ?? [];
    $path   = $r['path'];
    foreach (($recipe['path_params'] ?? []) as $k => $v) {
        $path = str_replace('{' . $k . '}', rawurlencode((string) $v), $path);
    }

    $query = array_merge($cfg['defaults']['query'] ?? [], $recipe['query'] ?? []);
    $body  = array_merge($cfg['defaults']['body']  ?? [], $recipe['body']  ?? []);
    $url   = $base . '/' . $r['prefix'] . $path . ($query ? '?' . http_build_query($query) : '');

    $hdr = $headers;
    $opts = ['http' => [
        'method'        => $method,
        'ignore_errors' => true,
        'timeout'       => 30,
    ]];
    if ($body && $method !== 'GET') {
        $payload = http_build_query($body);
        $hdr['Content-Type'] = 'application/x-www-form-urlencoded';
        $opts['http']['content'] = $payload;
    }
    $opts['http']['header'] = implode("\r\n", array_map(
        fn($k, $v) => "$k: $v", array_keys($hdr), $hdr
    ));

    $raw    = @file_get_contents($url, false, stream_context_create($opts));
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $status = (int) $m[1];
    }

    $decoded = json_decode((string) $raw, true);
    $record  = [
        'endpoint'   => $key,
        'method'     => $method,
        // The host is the one thing here that differs between two captures of
        // the same endpoint, and recording it would diff every fixture.
        'path'       => '/' . $r['prefix'] . $path,
        'status'     => $status,
        'json_valid' => json_last_error() === JSON_ERROR_NONE,
        'shape'      => $decoded === null ? null : shape($decoded),
    ];

    // Response bodies and raw excerpts are this tenant's rows, so they are
    // opt-in and land only in the gitignored fixtures directory.
    if ($withBody) {
        $record['url']         = $url;
        $record['body']        = $decoded;
        $record['raw_excerpt'] = $decoded === null ? substr((string) $raw, 0, 500) : null;
    }

    $file = $outDir . '/' . str_replace('/', '__', $key) . '.json';
    file_put_contents($file, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    if ($status >= 200 && $status < 400) { $ok++;   $mark = 'ok'; }
    else                                 { $fail++; $mark = "HTTP $status"; }
    printf("%-8s %-6s %s\n", $mark, $method, $key);
}

printf("\n%s: %d ok, %d failed, %d skipped -> migration/fixtures/%s/\n", $target, $ok, $fail, $skipped, $target);

/** Structural signature: keys and types only, so volatile ids/dates don't cause false diffs. */
function shape(mixed $v, int $depth = 0): mixed
{
    if ($depth > 12) return '...';
    if (is_array($v)) {
        if ($v === []) return [];
        if (array_is_list($v)) return [shape($v[0], $depth + 1)]; // list -> shape of first element
        $out = [];
        foreach ($v as $k => $vv) $out[$k] = shape($vv, $depth + 1);
        ksort($out);
        return $out;
    }
    return get_debug_type($v);
}
