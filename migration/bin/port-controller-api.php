<?php

declare(strict_types=1);

/**
 * The Symfony 2 controller idioms Rector does not cover.
 *
 *   1. $form->isValid() without isSubmitted(). Symfony 3 throws
 *      "Cannot check if an unsubmitted form is valid" instead of returning
 *      false, so every one of these is a 500 on first GET of the page.
 *   2. $dispatcher->dispatch('name', $event). Symfony 5 swapped the argument
 *      order to dispatch($event, 'name'); passing a string first is a
 *      TypeError.
 *   3. 'SomeBundle:Dir:' template prefixes that survived port-http.php because
 *      the file name is interpolated ('HospitalBundle:Report:'.$x.'.html.twig').
 *
 * Idempotent. Usage: php8.5 migration/bin/port-controller-api.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$next = $root . '/next';
$dryRun = in_array('--dry-run', $argv, true);

// Bundle class name -> module directory, the same map port-http.php builds.
$bundleToModule = [];

foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: [],
) as $file) {
    $class = basename($file, '.php');
    $dir = basename(dirname($file));

    if (!str_ends_with($dir, 'Bundle')) {
        continue;
    }

    $bundleToModule[$class] = substr($dir, 0, -strlen('Bundle'));
}

$bundleToModule['FOSUserBundle'] = '_fosuser';
$bundleToModule['FOSCommentBundle'] = '_foscomment';

function matchParen(string $code, int $openPos): int
{
    $depth = 0;
    $quote = null;

    for ($i = $openPos, $len = strlen($code); $i < $len; ++$i) {
        $ch = $code[$i];

        if ($quote !== null) {
            if ($ch === $quote && $code[$i - 1] !== '\\') {
                $quote = null;
            }
            continue;
        }

        if ($ch === '"' || $ch === "'") {
            $quote = $ch;
            continue;
        }

        if ($ch === '(') {
            ++$depth;
        } elseif ($ch === ')') {
            --$depth;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return -1;
}

function splitTopLevel(string $s): array
{
    $out = [];
    $depth = 0;
    $buf = '';
    $quote = null;

    for ($i = 0, $len = strlen($s); $i < $len; ++$i) {
        $ch = $s[$i];

        if ($quote !== null) {
            $buf .= $ch;
            if ($ch === $quote && $s[$i - 1] !== '\\') {
                $quote = null;
            }
            continue;
        }

        if ($ch === '"' || $ch === "'") {
            $quote = $ch;
            $buf .= $ch;
            continue;
        }

        if (str_contains('([{', $ch)) {
            ++$depth;
        } elseif (str_contains(')]}', $ch)) {
            --$depth;
        }

        if ($ch === ',' && $depth === 0) {
            $out[] = $buf;
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    if (trim($buf) !== '') {
        $out[] = $buf;
    }

    return $out;
}

$stats = ['isSubmitted guards' => 0, 'dispatch order' => 0, 'template prefixes' => 0, 'files' => 0];
$unmapped = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($next . '/src'));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;

    // 1. isValid() -> isSubmitted() && isValid(), on form variables only.
    $code = preg_replace_callback(
        '/(?<![\w>])(!?)\$(\w*[Ff]orm)->isValid\(\)/',
        static function (array $m) use (&$stats, &$code): string {
            // Already guarded in the same expression? Leave it be.
            $before = substr($code, max(0, $m[0][1] - 80), min(80, $m[0][1]));

            if (str_contains($before, $m[2][0] . '->isSubmitted()')) {
                return $m[0][0];
            }

            ++$stats['isSubmitted guards'];

            // !$form->isValid() guards an early return; keep that meaning by
            // treating an unsubmitted form as "not valid".
            return $m[1][0] === '!'
                ? "(!\${$m[2][0]}->isSubmitted() || !\${$m[2][0]}->isValid())"
                : "\${$m[2][0]}->isSubmitted() && \${$m[2][0]}->isValid()";
        },
        $code,
        -1,
        $count,
        PREG_OFFSET_CAPTURE,
    );

    // 2. dispatch('event.name', $event) -> dispatch($event, 'event.name')
    // The event argument is parsed with balanced parentheses; a regex stops at
    // the first ')' and would splice the name into the constructor call.
    $offset = 0;

    while (($pos = strpos($code, '->dispatch(', $offset)) !== false) {
        $open = $pos + strlen('->dispatch(') - 1;
        $close = matchParen($code, $open);

        if ($close === -1) {
            $offset = $pos + 11;
            continue;
        }

        $args = splitTopLevel(substr($code, $open + 1, $close - $open - 1));

        if (count($args) !== 2 || !preg_match('/^([\'"]).*\1$/s', trim($args[0]))) {
            $offset = $close;
            continue;
        }

        $replacement = '->dispatch(' . trim($args[1]) . ', ' . trim($args[0]) . ')';
        $code = substr($code, 0, $pos) . $replacement . substr($code, $close + 1);
        ++$stats['dispatch order'];
        $offset = $pos + strlen($replacement);
    }

    // 3. 'HospitalBundle:Report:' . $x . '.html.twig' -> 'Hospital/Report/' . ...
    $code = preg_replace_callback(
        '/([\'"])([A-Za-z]+Bundle):([A-Za-z0-9_]*):/',
        static function (array $m) use ($bundleToModule, &$stats, &$unmapped, $path, $next): string {
            $module = $bundleToModule[$m[2]] ?? null;

            if ($module === null) {
                $unmapped[$m[2]][] = str_replace($next . '/', '', $path);

                return $m[0];
            }

            ++$stats['template prefixes'];

            return $m[3] === ''
                ? $m[1] . $module . '/'
                : $m[1] . $module . '/' . $m[3] . '/';
        },
        $code,
    );

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-22s %d\n", $label, $count);
}

if ($unmapped !== []) {
    echo "\nreferences to bundles that never existed in this tree (left alone):\n";

    foreach ($unmapped as $bundle => $files) {
        printf("    %-24s %d in %s\n", $bundle, count($files), implode(', ', array_unique($files)));
    }
}
