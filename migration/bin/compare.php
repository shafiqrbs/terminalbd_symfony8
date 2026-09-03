<?php
/**
 * Fails if the ported SF8 app's JSON shape drifts from the recorded legacy shape.
 * Compares keys and value *types*, not values, so ids/timestamps don't trip it.
 *
 * usage: php migration/bin/compare.php [--only=substring]
 * exit 0 = wire-compatible, 1 = drift detected
 */
$only = null;
foreach (array_slice($argv, 1) as $a) {
    if (str_starts_with($a, '--only=')) $only = substr($a, 7);
}

$root = dirname(__DIR__, 2);
$legacyFiles = glob("$root/migration/fixtures/legacy/*.json") ?: [];

if (!$legacyFiles) {
    fwrite(STDERR, "No legacy fixtures. Run: php migration/bin/capture.php legacy\n");
    exit(1);
}

$drift = 0; $matched = 0; $missing = 0;

foreach ($legacyFiles as $lf) {
    $name = basename($lf);
    $old  = json_decode(file_get_contents($lf), true);
    if ($only !== null && !str_contains($old['endpoint'], $only)) continue;

    $nf = "$root/migration/fixtures/next/$name";
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
        $drift++;
        printf("DRIFT    %s\n", $old['endpoint']);
        foreach ($problems as $p) printf("           - %s\n", $p);
    } else {
        $matched++;
        printf("OK       %s\n", $old['endpoint']);
    }
}

printf("\n%d compatible, %d drifted, %d not yet ported\n", $matched, $drift, $missing);
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
