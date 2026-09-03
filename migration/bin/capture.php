<?php
/**
 * Records golden responses from an app so the SF2.8 -> SF8 port can be proven
 * wire-compatible. Run once against the legacy app, once against the new one,
 * then diff with compare.php.
 *
 * usage: php migration/bin/capture.php <legacy|next> [--only=substring]
 */
$target = $argv[1] ?? null;
if (!in_array($target, ['legacy', 'next'], true)) {
    fwrite(STDERR, "usage: capture.php <legacy|next> [--only=substring]\n");
    exit(1);
}
$only = null;
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--only=')) $only = substr($a, 7);
}

$root      = dirname(__DIR__, 2);
$endpoints = json_decode(file_get_contents("$root/migration/contract/api-endpoints.json"), true);
$cfg       = json_decode(file_get_contents("$root/migration/capture-config.json"), true);
$outDir    = "$root/migration/fixtures/$target";

$base = rtrim($cfg['base_url'], '/');
if (str_contains($base, 'CHANGE-ME')) {
    fwrite(STDERR, "Set base_url in migration/capture-config.json first.\n");
    exit(1);
}

$headers = array_filter($cfg['headers'] ?? []);
$ok = $fail = $skipped = 0;

foreach ($endpoints as $r) {
    $key = $r['prefix'] . $r['path'];
    if ($only !== null && !str_contains($key, $only)) continue;
    if (in_array($key, $cfg['skip'] ?? [], true)) { $skipped++; continue; }

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
        'endpoint'    => $key,
        'method'      => $method,
        'url'         => $url,
        'status'      => $status,
        'json_valid'  => json_last_error() === JSON_ERROR_NONE,
        'shape'       => $decoded === null ? null : shape($decoded),
        'body'        => $decoded,
        'raw_excerpt' => $decoded === null ? substr((string) $raw, 0, 500) : null,
    ];

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
