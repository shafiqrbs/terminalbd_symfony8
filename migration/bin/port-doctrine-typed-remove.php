<?php

declare(strict_types=1);

/**
 * Restores the legacy answer where Doctrine 3's typed signatures turned a
 * caught exception into an uncaught error.
 *
 * The legacy shape, copied faithfully into this tree:
 *
 *     $entity = $repo->find($id);
 *     if (empty($entity)) { $status = 'failed'; }   // no return
 *     try {
 *         $em->remove($entity);
 *         ...
 *     } catch (Exception) { $status = 'invalid'; }
 *
 * Doctrine 2 declared `remove($entity)` untyped and threw
 * ORMInvalidArgumentException for a non-object. That extends \Exception, so the
 * catch took it and the action answered 200 with its 'invalid' status. Doctrine
 * 3 declares `remove(object $entity)`, so PHP throws a **TypeError** instead -
 * which extends \Error, not \Exception - and the same catch does not see it.
 * The action 500s where it used to answer.
 *
 * The application bug (a guard that sets a status and then falls through to use
 * the null anyway) predates the port and is left exactly as it is; only the
 * catch is widened, by the one error type the new signature introduces. Widening
 * to \Throwable would swallow errors the legacy never caught either.
 *
 * Found by the golden API capture (§5.47) on flutter-api/poskeeper-stock-delete,
 * which is the only endpoint of the 96 that regressed. It gates: a site with
 * this shape whose catch cannot be widened is reported and fails the run.
 *
 * usage: php8.5 migration/bin/port-doctrine-typed-remove.php [next/src] [--dry-run]
 */

$root = dirname(__DIR__, 2);
$target = $root . '/next/src';
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!str_starts_with($arg, '-')) {
        $target = str_starts_with($arg, '/') ? $arg : $root . '/' . $arg;
    }
}

$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target)) as $f) {
    if ($f->getExtension() === 'php') {
        $files[] = $f->getPathname();
    }
}
sort($files);

$patched = 0;
$already = 0;
$unfixable = [];

foreach ($files as $path) {
    $lines = file($path);
    $changed = false;

    foreach ($lines as $i => $line) {
        // The catch that used to take Doctrine 2's exception.
        if (!preg_match('/catch\s*\(\s*\\\\?Exception\b/', $line)) {
            continue;
        }

        // Walk back to the try it belongs to, and require a remove()/persist()
        // of a plain variable inside it.
        $tryAt = null;
        for ($j = $i; $j > max(0, $i - 40); $j--) {
            if (preg_match('/\btry\s*\{/', $lines[$j])) {
                $tryAt = $j;
                break;
            }
        }

        if ($tryAt === null) {
            continue;
        }

        $body = implode('', array_slice($lines, $tryAt, $i - $tryAt));

        if (!preg_match('/->(remove|persist)\s*\(\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\)/', $body, $m)) {
            continue;
        }

        $var = preg_quote($m[2], '/');
        $before = implode('', array_slice($lines, max(0, $tryAt - 20), $tryAt - max(0, $tryAt - 20)));

        // The variable has to be one a lookup can leave null...
        if (!preg_match('/' . $var . '\s*=\s*[^;]*->(find|findOneBy)\s*\(/', $before)) {
            continue;
        }

        // ...and the guard has to fall through rather than leave the function.
        // A guard that returns or throws never reaches remove() with a null, and
        // eleven of the twelve sites that match the shape above do exactly that.
        if (!preg_match('/if\s*\(\s*(empty\(\s*' . $var . '\s*\)|!\s*' . $var . ')\s*\)\s*\{(.*?)\}/s', $before, $g)) {
            continue;
        }

        if (preg_match('/\b(return|throw|exit|die)\b/', $g[2])) {
            continue;
        }

        if (str_contains($line, 'TypeError')) {
            $already++;
            continue;
        }

        $new = preg_replace('/catch\s*\(\s*(\\\\?Exception\b)/', 'catch (\\TypeError | $1', $line, 1);

        if ($new === null || $new === $line) {
            $unfixable[] = substr($path, strlen($root) + 1) . ':' . ($i + 1);
            continue;
        }

        $lines[$i] = $new;
        $changed = true;
        $patched++;
        printf("  %s:%d\n", substr($path, strlen($root) + 1), $i + 1);
    }

    if ($changed && !$dryRun) {
        file_put_contents($path, implode('', $lines));
    }
}

printf(
    "catches widened for Doctrine 3's typed remove()/persist(): %d%s\n",
    $patched,
    $already > 0 ? sprintf(' (%d already done)', $already) : ''
);

if ($unfixable !== []) {
    fwrite(STDERR, "could not widen:\n  " . implode("\n  ", $unfixable) . "\n");
    exit(1);
}
