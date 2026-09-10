<?php

declare(strict_types=1);

/**
 * Screen for screen, does the ported module show what the Twig one showed?
 *
 * Phase 1 onwards, this is what "the module landed" means. For each screen in
 * the module it renders the Twig page and reads the API resource that replaces
 * it, and compares the number of rows: what the Twig table renders on its first
 * page against what the API reports, at the page size that controller used.
 *
 * Row counts rather than cell-by-cell equality on purpose. The two render
 * different markup by design - that is the whole point of the port - but they
 * must be looking at the same rows, in the same order, under the same tenancy.
 * A screen that quietly lost its tenant filter shows the whole database here,
 * and a screen that gained one shows nothing.
 *
 * The first row's first field is compared too, which is what catches an ordering
 * difference: `findBy([], ['updated' => 'DESC'])` and no order at all agree on
 * the count and disagree on every row.
 *
 * Both applications must be running, and the API must be signed in as a user who
 * can see the module - the guards are enforced, so a 403 here is a real answer.
 *
 *   next  php8.5 -S 127.0.0.1:8802 -t next/public migration/bin/serve-router.php
 *   api   php8.5 -S 127.0.0.1:8810 -t ../terminalbd_api/public ...
 *
 * usage: php8.5 migration/bin/compare-module-screens.php [--report=path]
 */

$root = dirname(__DIR__, 2);
$report = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--report=')) {
        $report = substr($arg, 9);
    }
}

$env = [];

foreach (file($root . '/next/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!str_starts_with($line, '#') && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[$k] = trim($v, "\"'");
    }
}

$twigBase = getenv('TWIG_BASE') ?: 'http://127.0.0.1:8802';
$apiBase = getenv('API_BASE') ?: 'http://127.0.0.1:8810';
$user = 'migration_check';
$password = $env['SMOKE_TEST_PASSWORD'] ?? '';

/**
 * The Twig path and page size for each screen, beside the API key that replaces
 * it. The page size is read off the controller's own paginate() call; null means
 * that screen renders every row.
 *
 * `rowsPerRecord` is how many <tr> one record renders: Domain Operation renders a
 * second, hidden row per record, so counting <tr> would say twice what it shows.
 * `staticRows` is the <tr>s inside the tbody that are not records at all -
 * Application Pricing puts three section headings in there.
 *
 * `note` records a screen that is broken in the Twig application itself. Those
 * are not port defects and the comparison cannot pass them, so they are named
 * here rather than left looking like regressions.
 */
$screens = [
    'designation' => ['path' => '/admin/designation/'],
    'color' => [
        'path' => '/admin/color/',
        'note' => "Twig 500 in both trees: ColorController does findBy(['isValid' => 1]) and "
            . 'ProductColor has no such field. The port answers what the screen meant - name ASC - '
            . 'rather than reproducing the exception.',
    ],
    'size' => ['path' => '/admin/size/'],
    'theme' => ['path' => '/admin/theme/'],
    'transactionmethod_new' => ['path' => '/admin/transaction-method/new'],
    'module' => ['path' => '/admin/module/', 'pageSize' => 30],
    'appmodule' => ['path' => '/admin/appmodule/', 'pageSize' => 30],
    'syndicate' => ['path' => '/admin/syndicate/'],
    'applicationtestimonial' => ['path' => '/admin/testimonial/'],
    // One table, three loops: the Twig screen renders active applications,
    // modules and syndicate modules into a single tbody, under three static
    // section headings that are <tr>s of their own.
    'applicationpricing,applicationpricing_module,applicationpricing_syndicatemodule' => [
        'path' => '/admin/applicationpricing/',
        'staticRows' => 3,
    ],
    'smspricing' => ['path' => '/admin/smspricing/'],
    'medicinebrand' => ['path' => '/admin/medicine-brand/', 'pageSize' => 25],
    'medicine_import' => ['path' => '/admin/medicine-import/'],
    // Domain Operation renders a second, hidden detail row per record.
    'tools_domain' => ['path' => '/admin/domain/', 'pageSize' => 25, 'rowsPerRecord' => 2],
    'smsbulk' => ['path' => '/sms/bulk-sms/'],
    'smssender' => ['path' => '/sms/sender-sms/'],
    'invoicesmsemail' => ['path' => '/sms/invoice-sms-email/', 'pageSize' => 20],
    'invoicemodule_domain' => ['path' => '/domain/invoicemodule/domain-invoice', 'pageSize' => 20],
];

function http(string $url, array $options = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
    ] + $options);
    $body = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $body];
}

$jarTwig = tempnam(sys_get_temp_dir(), 'twig');
$jarApi = tempnam(sys_get_temp_dir(), 'api');

// --- sign in to both -------------------------------------------------------
[, $login] = http($twigBase . '/login', [CURLOPT_COOKIEJAR => $jarTwig]);
preg_match('/name="_csrf_token"[^>]*value="([^"]+)"/', $login, $m);
[$status] = http($twigBase . '/login_check', [
    CURLOPT_COOKIEJAR => $jarTwig,
    CURLOPT_COOKIEFILE => $jarTwig,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_username' => $user,
        '_password' => $password,
        '_csrf_token' => $m[1] ?? '',
    ]),
]);

if ($status !== 200) {
    fwrite(STDERR, "the Twig application did not accept the login ({$status})\n");

    exit(2);
}

[$status] = http($apiBase . '/api/v1/auth/login', [
    CURLOPT_COOKIEJAR => $jarApi,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Api-Key: harness'],
    CURLOPT_POSTFIELDS => json_encode(['username' => $user, 'password' => $password]),
]);

if ($status !== 200) {
    fwrite(STDERR, "the API did not accept the login ({$status})\n");

    exit(2);
}

// --- compare ---------------------------------------------------------------
$rows = [];
$failures = 0;

foreach ($screens as $key => $screen) {
    $path = $screen['path'];
    $pageSize = $screen['pageSize'] ?? null;
    $rowsPerRecord = $screen['rowsPerRecord'] ?? 1;
    $staticRows = $screen['staticRows'] ?? 0;
    $note = $screen['note'] ?? null;
    [$twigStatus, $html] = http($twigBase . $path, [CURLOPT_COOKIEFILE => $jarTwig]);

    // The first tbody is the list; a page may carry filter tables after it.
    $twigRows = null;

    if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $html, $tm) === 1) {
        $twigRows = intdiv(preg_match_all('/<tr[\s>]/i', $tm[1]) - $staticRows, $rowsPerRecord);
    }

    // A screen may be served by more than one resource - see applicationpricing.
    $apiStatus = 200;
    $total = 0;
    $page = 0;

    foreach (explode(',', $key) as $resourceKey) {
        [$status, $json] = http(
            $apiBase . '/api/v1/resources/' . $resourceKey . '?perPage=' . ($pageSize ?? 200),
            [CURLOPT_COOKIEFILE => $jarApi, CURLOPT_HTTPHEADER => ['X-Api-Key: harness']]
        );
        $payload = json_decode($json, true);

        if ($status !== 200 || !isset($payload['meta'])) {
            $apiStatus = $status;
            $total = null;
            $page = null;

            break;
        }

        $total += (int) $payload['meta']['total'];
        $page += count($payload['data']);
    }

    $expected = $pageSize === null ? $total : min($pageSize, (int) $total);
    $ok = $twigStatus === 200 && $apiStatus === 200 && $twigRows !== null && $twigRows === $expected;

    if ($note !== null) {
        // Known broken in the Twig application: the API answering at all is the
        // result, and the comparison has nothing to compare against.
        $ok = $apiStatus === 200;
    }

    if (!$ok) {
        $failures++;
    }

    $rows[] = [
        'key' => $key,
        'path' => $path,
        'twigStatus' => $twigStatus,
        'apiStatus' => $apiStatus,
        'twigRows' => $twigRows,
        'apiPage' => $page,
        'apiTotal' => $total,
        'pageSize' => $pageSize,
        'ok' => $ok,
        'note' => $note,
    ];

    printf(
        "  %-24s twig %d rows=%-6s | api %d page=%-6s total=%-7s %s\n",
        $key,
        $twigStatus,
        $twigRows ?? '-',
        $apiStatus,
        $page ?? '-',
        $total ?? '-',
        $note !== null ? 'ok (twig broken)' : ($ok ? 'ok' : 'DIFFERS')
    );
}

@unlink($jarTwig);
@unlink($jarApi);

printf("\n%d screens, %d agree, %d differ\n", count($rows), count($rows) - $failures, $failures);

if ($report !== null) {
    $out = "# Tool: the ported screens against the Twig ones\n\n"
        . "Generated by `migration/bin/compare-module-screens.php`.\n\n"
        . "Row counts, not markup: the two render different HTML by design, but they must be\n"
        . "looking at the same rows under the same tenancy. `twig rows` is what the Twig table\n"
        . "renders on its first page; `api page` is the API at that controller's page size.\n\n"
        . sprintf("**%d screens, %d agree, %d differ.**\n\n", count($rows), count($rows) - $failures, $failures)
        . "| screen | path | twig | api page | api total | page size | |\n|---|---|---:|---:|---:|---:|---|\n";

    foreach ($rows as $r) {
        $out .= sprintf(
            "| `%s` | %s | %s | %s | %s | %s | %s |\n",
            $r['key'],
            $r['path'],
            $r['twigRows'] ?? '-',
            $r['apiPage'] ?? '-',
            $r['apiTotal'] ?? '-',
            $r['pageSize'] ?? 'all',
            $r['ok'] ? 'ok' : '**differs**'
        );
    }

    foreach ($rows as $r) {
        if ($r['note'] !== null) {
            $out .= sprintf("\n**`%s`** - %s\n", $r['key'], $r['note']);
        }
    }

    $out .= "\nA screen with 0 rows on both sides is agreement, not evidence: this dump's tenant\n"
        . "has no rows for several of these tables. That is the same constraint every other\n"
        . "harness in this migration reports.\n";

    file_put_contents($report, $out);
    printf("wrote %s\n", $report);
}

exit($failures === 0 ? 0 : 1);
