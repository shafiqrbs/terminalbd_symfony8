<?php
/**
 * Fails if the ported SF8 app's JSON shape drifts from the recorded legacy shape.
 * Compares keys and value *types*, not values, so ids/timestamps don't trip it.
 *
 * Seven endpoints differ for reasons that are not port defects - five whose
 * controller action exists in neither tree, two that fail on the legacy side
 * because the scratch database carries this application's schema. They are
 * listed with their reasons in migration/contract/api-drift-accepted.json and
 * reported as KNOWN. Everything else fails, which is what makes this a deploy
 * gate rather than a report: a new drift is a wire change to applications
 * already in the field.
 *
 * usage: php migration/bin/compare.php [--only=substring]
 *            [--baseline=legacy|next|next-live] [--target=next|api]
 * exit 0 = wire-compatible, 1 = drift outside the accepted list
 */
$only = null;
// Which capture is held against which. The default is the recorded contract:
// legacy against next.
//
// `--baseline=next --target=api` is phase 9's gate, and it is the one that
// means something now. The committed legacy fixtures were taken before five
// module config rows were seeded into the scratch database (phases 4 to 8), so
// legacy-against-anything now reports drift that is the DATABASE moving, not
// the port. Capturing next and api in one run and comparing those two is immune
// to that: same data, same moment, two applications.
$target = 'next';
$baseline = 'legacy';

foreach (array_slice($argv, 1) as $a) {
    if (str_starts_with($a, '--only=')) $only = substr($a, 7);
    if (str_starts_with($a, '--target=')) $target = substr($a, 9);
    if (str_starts_with($a, '--baseline=')) $baseline = substr($a, 11);
}

foreach (['target' => $target, 'baseline' => $baseline] as $what => $value) {
    if (!in_array($value, ['legacy', 'next', 'next-live', 'api'], true)) {
        fwrite(STDERR, "unknown {$what} '{$value}': expected legacy, next or api\n");
        exit(2);
    }
}

if ($target === $baseline) {
    fwrite(STDERR, "baseline and target are both '{$target}'\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
$legacyFiles = glob("$root/migration/fixtures/$baseline/*.json") ?: [];

if (!$legacyFiles) {
    fwrite(STDERR, "No $baseline fixtures. Run: php migration/bin/capture.php $baseline\n");
    exit(1);
}

$acceptedPath = "$root/migration/contract/api-drift-accepted.json";
$acceptedRaw = is_file($acceptedPath)
    ? array_filter(json_decode((string) file_get_contents($acceptedPath), true) ?: [],
        static fn (string $k): bool => !str_starts_with($k, '_'), ARRAY_FILTER_USE_KEY)
    : [];

// An entry is either a reason, or a reason scoped to particular targets. Scoping
// exists because a difference can be real for one comparison and absent from
// another: android-api-portal/apps drifts only when the API is the target, since
// the legacy and next/ both answer 302 there and only the API answers 404.
$accepted = [];

foreach ($acceptedRaw as $endpoint => $entry) {
    if (is_array($entry) && isset($entry['targets'])) {
        if (!in_array($target, $entry['targets'], true)) {
            continue;
        }

        $accepted[$endpoint] = $entry['reason'] ?? '';

        continue;
    }

    $accepted[$endpoint] = $entry;
}

$drift = 0; $matched = 0; $missing = 0; $known = 0; $staleAccepted = $accepted;

foreach ($legacyFiles as $lf) {
    $name = basename($lf);
    $old  = json_decode(file_get_contents($lf), true);
    if ($only !== null && !str_contains($old['endpoint'], $only)) continue;

    $nf = "$root/migration/fixtures/$target/$name";
    if (!is_file($nf)) {
        printf("MISSING  %s  (not yet ported)\n", $old['endpoint']);
        $missing++;
        continue;
    }
    $new = json_decode(file_get_contents($nf), true);

    $problems = [];
    if ($old['status'] !== $new['status']) {
        $problems[] = sprintf('status %d -> %d', $old['status'], $new['status']);
    }
    foreach (diffShape($old['shape'], $new['shape']) as $d) {
        $problems[] = $d;
    }

    if ($problems) {
        unset($staleAccepted[$old['endpoint']]);

        if (isset($accepted[$old['endpoint']])) {
            $known++;
            printf("KNOWN    %s\n", $old['endpoint']);
            foreach ($problems as $p) printf("           - %s\n", $p);
        } else {
            $drift++;
            printf("DRIFT    %s\n", $old['endpoint']);
            foreach ($problems as $p) printf("           - %s\n", $p);
        }
    } else {
        $matched++;
        printf("OK       %s\n", $old['endpoint']);
    }
}

printf(
    "\n%d compatible, %d known (see api-drift-accepted.json), %d drifted, %d not yet ported\n",
    $matched,
    $known,
    $drift,
    $missing
);

// An accepted entry that no longer drifts has been fixed, and leaving it listed
// would hide the next regression on that endpoint behind a stale excuse.
//
// Only when the baseline is the legacy capture, because that is what the entries
// describe. Two of them are legacy-side schema drift, and comparing next-live
// against api - neither of which is the legacy - naturally does not reproduce
// them; reporting those as fixed would be telling somebody to delete a reason
// that still applies.
if ($only === null && $baseline === 'legacy' && $staleAccepted !== []) {
    fwrite(STDERR, "\naccepted drift that no longer happens - remove from api-drift-accepted.json:\n  "
        . implode("\n  ", array_keys($staleAccepted)) . "\n");
    exit(1);
}

exit($drift > 0 ? 1 : 0);

function diffShape(mixed $a, mixed $b, string $path = '$'): array
{
    if (is_array($a) && is_array($b)) {
        $out = [];
        foreach (array_diff(array_keys($a), array_keys($b)) as $k) {
            $out[] = "$path.$k: dropped by new app";
        }
        foreach (array_diff(array_keys($b), array_keys($a)) as $k) {
            $out[] = "$path.$k: added by new app";
        }
        foreach (array_intersect(array_keys($a), array_keys($b)) as $k) {
            $out = array_merge($out, diffShape($a[$k], $b[$k], "$path.$k"));
        }
        return $out;
    }
    if ($a === $b) return [];
    // int/float and null/typed drift are the usual JSON serializer regressions.
    return [sprintf('%s: %s -> %s', $path, json_encode($a), json_encode($b))];
}
