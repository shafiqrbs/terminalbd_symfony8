<?php

declare(strict_types=1);

/**
 * Symfony 3 form option renames, including the one that silently corrupts data.
 *
 *   empty_value        -> placeholder
 *   virtual            -> inherit_data
 *   cascade_validation -> dropped (Symfony 3 cascades automatically)
 *   choices            -> flipped: Symfony 2 wrote value => label, Symfony 3+
 *                         reads label => value. Left alone, every choice field
 *                         would store its label instead of its value.
 *
 * A call that already carries `choices_as_values` is in Symfony 3 order
 * already: those are left as they are and the (removed) option is dropped.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-form-options.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

function splitTopLevel(string $s, string $sep = ','): array
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

        if ($ch === $sep && $depth === 0) {
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

/** Finds the array literal that starts at $pos ('[' or 'array('), returns [inner, endPos]. */
function readArray(string $code, int $pos): ?array
{
    $len = strlen($code);

    while ($pos < $len && ctype_space($code[$pos])) {
        ++$pos;
    }

    if ($code[$pos] === '[') {
        $open = '[';
        $close = ']';
        $start = $pos + 1;
    } elseif (substr($code, $pos, 6) === 'array(') {
        $open = '(';
        $close = ')';
        $start = $pos + 6;
    } else {
        return null;
    }

    $depth = 1;
    $quote = null;

    for ($i = $start; $i < $len; ++$i) {
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

        if ($ch === $open) {
            ++$depth;
        } elseif ($ch === $close) {
            --$depth;
            if ($depth === 0) {
                return [substr($code, $start, $i - $start), $i, $start];
            }
        }
    }

    return null;
}

/** Helpers returning a Symfony 2 value => label list at runtime. */
const RUNTIME_VALUE_LABEL_LISTS = ['getAccessRoleGroup', 'getAppRoleGroup'];

$stats = ['runtime choice lists flipped' => 0, 'empty_value' => 0, 'virtual' => 0, 'cascade_validation' => 0, 'choices flipped' => 0, 'choices_as_values dropped' => 0, 'choices skipped' => 0, 'files' => 0];
$skipped = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);
    $original = $code;

    $code = preg_replace('/([\'"])empty_value\1(\s*=>)/', "'placeholder'$2", $code, -1, $n);
    $stats['empty_value'] += $n;

    $code = preg_replace('/([\'"])virtual\1(\s*=>)/', "'inherit_data'$2", $code, -1, $n);
    $stats['virtual'] += $n;

    // 'cascade_validation' => true,  -> gone
    $code = preg_replace('/\s*([\'"])cascade_validation\1\s*=>\s*[^,\]\)]+,?/', '', $code, -1, $n);
    $stats['cascade_validation'] += $n;

    // ---- choices ---------------------------------------------------------
    $offset = 0;

    while (($pos = strpos($code, "'choices'", $offset)) !== false) {
        $arrow = strpos($code, '=>', $pos);
        $array = $arrow === false ? null : readArray($code, $arrow + 2);

        if ($array === null) {
            $offset = $pos + 9;
            continue;
        }

        [$inner, $endPos, $startPos] = $array;

        // Is this call already in Symfony 3 order? Look only inside the
        // enclosing ->add()/create() call, not at the neighbouring ones.
        $callStart = strrpos(substr($code, 0, $pos), '->add(');
        $callStart = $callStart === false ? max(0, $pos - 400) : $callStart;
        $window = substr($code, $callStart, $endPos - $callStart);

        if (str_contains($window, 'choices_as_values')) {
            $offset = $endPos;
            continue;
        }

        $pairs = splitTopLevel($inner);
        $flipped = [];
        $ok = true;

        foreach ($pairs as $pair) {
            if (trim($pair) === '') {
                continue;
            }

            $parts = splitTopLevel($pair, '=');
            // Split on the '=>' arrow only.
            if (!preg_match('/^(.*?)=>(.*)$/s', $pair, $pm) || substr_count($pair, '=>') !== 1) {
                $ok = false;
                break;
            }

            $key = trim($pm[1]);
            $value = trim($pm[2]);

            // Only literal scalars can be flipped safely.
            $isLiteral = static fn (string $v): bool => (bool) preg_match('/^([\'"]).*\1$/s', $v) || (bool) preg_match('/^-?\d+$/', $v);

            if (!$isLiteral($key) || !$isLiteral($value)) {
                $ok = false;
                break;
            }

            $flipped[] = $value . ' => ' . $key;
        }

        if (!$ok || $flipped === []) {
            if (!$ok) {
                ++$stats['choices skipped'];
                $skipped[] = str_replace($root . '/', '', $path) . ': ' . trim(substr($inner, 0, 60));
            }
            $offset = $endPos;
            continue;
        }

        $replacement = implode(', ', $flipped);
        $code = substr($code, 0, $startPos) . $replacement . substr($code, $endPos);
        ++$stats['choices flipped'];
        $offset = $startPos + strlen($replacement);
    }

    $code = preg_replace('/\s*([\'"])choices_as_values\1\s*=>\s*[^,\]\)]+,?/', '', $code, -1, $n);
    $stats['choices_as_values dropped'] += $n;

    // Choice lists built at runtime cannot be flipped in place. The role lists
    // are the ones that matter - UserRepository returns
    // [group => [ROLE_X => 'Label']], Symfony 2's order - and they are read by
    // non-form code too, so they are flipped at the call site.
    foreach (RUNTIME_VALUE_LABEL_LISTS as $method) {
        $code = preg_replace(
            '/([\'"]choices[\'"]\s*=>\s*)\$this->' . preg_quote($method, '/') . '\(\)/',
            '$1ChoiceList::flip($this->' . $method . '())',
            $code,
            -1,
            $n,
        );
        $stats['runtime choice lists flipped'] += $n;

        if ($n > 0 && !str_contains($code, 'use App\\Form\\ChoiceList;')) {
            $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse App\\Form\\ChoiceList;\n", $code, 1);
        }
    }

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-28s %d\n", $label, $count);
}

if ($skipped !== []) {
    echo "\nchoices left alone (not literal - check by hand):\n";
    foreach (array_slice($skipped, 0, 30) as $line) {
        echo "  {$line}\n";
    }
}
