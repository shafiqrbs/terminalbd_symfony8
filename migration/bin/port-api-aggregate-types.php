<?php

declare(strict_types=1);

/**
 * Keeps DQL COUNT() results on the wire as the strings the mobile clients were
 * built against.
 *
 * PHP 8.1 made PDO MySQL return native ints where it used to return strings,
 * and Doctrine ORM 3 goes further: it hydrates a DQL COUNT() through its own
 * integer type, above the driver, so `PDO::ATTR_STRINGIFY_FETCHES` does not
 * reach it. A `count(s.id) as voucher` that Doctrine 2 handed back as "0" now
 * arrives as 0, and `json_encode` writes `0` where it wrote `"0"`.
 *
 * The golden capture (§5.47) caught exactly one instance -
 * android-api/dashboard's `salesVoucher` - and it is the only one: of the 107
 * repository methods the Bindu API controllers call, one selects a COUNT alias,
 * and every other place that alias reaches a response goes through
 * number_format(), which returns a string on both stacks. Entity fields are not
 * affected either; Doctrine casts those through the mapping on both stacks.
 *
 * So this cast is narrow on purpose. `PDO::ATTR_STRINGIFY_FETCHES` was tried
 * first and rejected: it fixes MAX()/MIN() and raw SQL, no MAX()/MIN() alias
 * reaches the API, and a re-capture with it on was identical - a global change
 * to every query in the application, buying nothing measurable.
 *
 * It gates. A COUNT alias that reaches an API response uncast is a wire change
 * no test would otherwise catch, so a new one fails the run rather than
 * shipping quietly.
 *
 * usage: php8.5 migration/bin/port-api-aggregate-types.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

/* ----------------------------- repository method -> its COUNT() aliases ---- */

$countAliases = [];

foreach (glob("$root/next/src/*/Repository/*.php") ?: [] as $file) {
    $src = (string) file_get_contents($file);

    if (!preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $src, $m, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    foreach ($m[1] as $i => [$name, $offset]) {
        $end = $m[1][$i + 1][1] ?? strlen($src);
        $body = substr($src, $offset, $end - $offset);

        if (preg_match_all('/\bcount\s*\([^)]*\)\s+as\s+([a-zA-Z0-9_]+)/i', $body, $mm)) {
            foreach ($mm[1] as $alias) {
                $countAliases[$name][$alias] = true;
            }
        }
    }
}

/* ------------------------------------- cast them where they reach a response */

$cast = 0;
$found = 0;

foreach (glob("$root/next/src/Bindu/Controller/*.php") ?: [] as $file) {
    $src = (string) file_get_contents($file);
    $original = $src;

    // $var = ...->someRepositoryMethod(...)  ->  which aliases $var can carry
    if (!preg_match_all('/(\$[a-zA-Z0-9_]+)\s*=\s*[^;\n]*->([a-zA-Z0-9_]+)\s*\(/', $src, $m, PREG_SET_ORDER)) {
        continue;
    }

    $carries = [];
    foreach ($m as $set) {
        foreach (array_keys($countAliases[$set[2]] ?? []) as $alias) {
            $carries[$set[1]][$alias] = true;
        }
    }

    foreach ($carries as $var => $aliases) {
        foreach (array_keys($aliases) as $alias) {
            $pattern = '/(?<!\(string\)\s)(?<!\(string\))' . preg_quote($var, '/') . '\s*\[\s*[\'"]' . preg_quote($alias, '/') . '[\'"]\s*\]/';

            $src = preg_replace_callback($pattern, static function (array $mm) use (&$cast, &$found, $src): string {
                $found++;
                $at = strpos($src, $mm[0]);

                // number_format() already returns a string on both stacks, and
                // arithmetic on the value never reaches the wire as itself.
                $line = $at === false ? '' : substr($src, max(0, strrpos(substr($src, 0, $at), "\n") ?: 0), 200);

                if (str_contains($line, 'number_format')) {
                    return $mm[0];
                }

                $cast++;

                return '(string) ' . $mm[0];
            }, $src);
        }
    }

    if ($src !== $original) {
        printf("  %s\n", substr($file, strlen($root) + 1));

        if (!$dryRun) {
            file_put_contents($file, $src);
        }
    }
}

printf("COUNT() aliases reaching an API response: %d, cast to string: %d\n", $found, $cast);
