<?php

declare(strict_types=1);

/**
 * Symfony 3 removed instance-passing to createForm()/->add().
 *
 * Every `new XType($a, $b)` call site becomes `XType::class` plus form
 * options, and the type itself stops taking constructor arguments: the values
 * arrive as options and are assigned to the same properties at the top of
 * buildForm(), so the rest of the type's body is untouched.
 *
 *   $this->createForm(new ItemType($globalOption), $entity, ['x' => 1])
 *   $this->createForm(ItemType::class, $entity, ['x' => 1, 'globalOption' => $globalOption])
 *
 * Idempotent. Usage: php8.5 migration/bin/port-form-instances.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

/**
 * Splits a PHP argument list on top-level commas.
 *
 * @return string[]
 */
function splitArgs(string $args): array
{
    $out = [];
    $depth = 0;
    $buf = '';
    $quote = null;

    for ($i = 0, $len = strlen($args); $i < $len; ++$i) {
        $ch = $args[$i];

        if ($quote !== null) {
            $buf .= $ch;
            if ($ch === $quote && ($i === 0 || $args[$i - 1] !== '\\')) {
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
            $out[] = trim($buf);
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    if (trim($buf) !== '') {
        $out[] = trim($buf);
    }

    return $out;
}

/** Reads from an opening brace to its match, returning the closing offset. */
function matchBrace(string $code, int $openPos): int
{
    $depth = 0;

    for ($i = $openPos, $len = strlen($code); $i < $len; ++$i) {
        if ($code[$i] === '{') {
            ++$depth;
        } elseif ($code[$i] === '}') {
            --$depth;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return $len - 1;
}

/** Reads from an opening parenthesis to its match, returning [inner, endOffset]. */
function readBalanced(string $code, int $openPos): array
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
                return [substr($code, $openPos + 1, $i - $openPos - 1), $i];
            }
        }
    }

    return ['', -1];
}

// ---------------------------------------------------------------------------
// 1. Index every form type that still takes constructor arguments.
// ---------------------------------------------------------------------------

$typeIndex = [];
$knownTypes = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $code = file_get_contents($file->getPathname());

    if (!preg_match('/class\s+(\w+)\s+extends\s+(?:AbstractType|\w*FormType)\b/', $code, $m)) {
        continue;
    }

    $knownTypes[$m[1]] = true;

    if (!preg_match('/(?:public|protected|private)?\s*function\s+__construct\s*\(/', $code, $cm, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    $sigOpen = strpos($code, '(', $cm[0][1]);
    [$ctorParams, $sigEnd] = readBalanced($code, $sigOpen);
    $bodyOpen = strpos($code, '{', $sigEnd);
    $bodyEnd = matchBrace($code, $bodyOpen);
    $ctor = [1 => $ctorParams, 2 => substr($code, $bodyOpen, $bodyEnd - $bodyOpen)];

    $params = [];
    foreach (splitArgs($ctor[1]) as $param) {
        if (preg_match('/\$(\w+)/', $param, $pm)) {
            $params[] = $pm[1];
        }
    }

    if ($params === []) {
        continue;
    }

    // param name => property it is assigned to
    $assign = [];
    foreach ($params as $param) {
        if (preg_match('/\$this->(\w+)\s*=\s*\$' . preg_quote($param, '/') . '\s*;/', $ctor[2], $am)) {
            $assign[$param] = $am[1];
        }
    }

    preg_match('/namespace\s+([^;]+);/', $code, $ns);

    $typeIndex[$m[1]] = [
        'file' => $file->getPathname(),
        'fqcn' => trim($ns[1] ?? '') . '\\' . $m[1],
        'params' => $params,
        'assign' => $assign,
    ];
}

printf("form types taking constructor arguments: %d\n", count($typeIndex));

// ---------------------------------------------------------------------------
// 2. Rewrite the call sites.
// ---------------------------------------------------------------------------

$converted = 0;
$unknown = [];
$touched = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, 'new ') || (!str_contains($code, 'createForm') && !str_contains($code, '->add(') && !str_contains($code, '->create('))) {
        continue;
    }

    $original = $code;
    $guard = 0;

    while (preg_match('/(createForm|->add|->create|->createNamed)\(/', $code, $m, PREG_OFFSET_CAPTURE) !== false) {
        // Walk every call of interest; rewrite the first that still holds an
        // instantiated form type, then start over so offsets stay valid.
        $rewrote = false;

        if (++$guard > 500) {
            break;
        }

        foreach (['createForm(', '->add(', '->create(', '->createNamed('] as $needle) {
            $offset = 0;

            while (($pos = strpos($code, $needle, $offset)) !== false) {
                $openPos = $pos + strlen($needle) - 1;
                [$inner, $end] = readBalanced($code, $openPos);
                $offset = $pos + strlen($needle);

                if ($end === -1 || !str_contains($inner, 'new ')) {
                    continue;
                }

                $args = splitArgs($inner);
                $typeArgIndex = null;

                foreach ($args as $i => $arg) {
                    if (preg_match('/^new\s+\\\\?([\w\\\\]+)\s*\(/', $arg, $tm)) {
                        $short = substr($tm[1], strrpos($tm[1], '\\') === false ? 0 : strrpos($tm[1], '\\') + 1);

                        if (isset($typeIndex[$short]) || str_ends_with($short, 'Type')) {
                            $typeArgIndex = $i;
                            break;
                        }
                    }
                }

                if ($typeArgIndex === null) {
                    continue;
                }

                $arg = $args[$typeArgIndex];
                preg_match('/^new\s+\\\\?([\w\\\\]+)\s*\(/', $arg, $tm);
                $short = substr($tm[1], strrpos($tm[1], '\\') === false ? 0 : strrpos($tm[1], '\\') + 1);

                $ctorOpen = strpos($arg, '(');
                [$ctorArgsRaw] = readBalanced($arg, $ctorOpen);
                $ctorArgs = splitArgs($ctorArgsRaw);

                if (!isset($typeIndex[$short])) {
                    // A type that exists but declares no constructor was being
                    // handed arguments PHP silently discarded; drop them.
                    if ($ctorArgs !== [] && !isset($knownTypes[$short])) {
                        $unknown[$short] = ($unknown[$short] ?? 0) + 1;
                        $offset = $end;
                        continue;
                    }
                    $params = [];
                } else {
                    $params = $typeIndex[$short]['params'];
                }

                // new XType(a, b) -> XType::class, with a/b moved to options
                $args[$typeArgIndex] = $short . '::class';

                $pairs = [];
                foreach ($ctorArgs as $i => $value) {
                    if (!isset($params[$i])) {
                        continue;
                    }
                    $pairs[] = "'" . $params[$i] . "' => " . $value;
                }

                if ($pairs !== []) {
                    // The options array is the argument after the data for
                    // createForm(type, data, options) and ->add(name, type, options).
                    $optionsIndex = $typeArgIndex + ($needle === 'createForm(' || $needle === '->create(' ? 2 : 1);

                    if (isset($args[$optionsIndex]) && preg_match('/^(array\(|\[)/', trim($args[$optionsIndex]))) {
                        $existing = trim($args[$optionsIndex]);
                        $isArrayFn = str_starts_with($existing, 'array(');
                        $body = substr($existing, $isArrayFn ? 6 : 1, -1);
                        $body = rtrim(rtrim(trim($body)), ',');
                        $joined = $body === ''
                            ? implode(', ', $pairs)
                            : $body . ', ' . implode(', ', $pairs);
                        $args[$optionsIndex] = ($isArrayFn ? 'array(' : '[') . $joined . ($isArrayFn ? ')' : ']');
                    } else {
                        while (count($args) < $optionsIndex) {
                            $args[] = 'null';
                        }
                        $args[$optionsIndex] = '[' . implode(', ', $pairs) . ']';
                        ksort($args);
                    }
                }

                $replacement = $needle . implode(', ', $args) . ')';
                $code = substr($code, 0, $pos) . $replacement . substr($code, $end + 1);
                ++$converted;
                $rewrote = true;
                break 2;
            }
        }

        if (!$rewrote) {
            break;
        }
    }

    if ($code !== $original) {
        ++$touched;

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

// mark-manual-forms.php runs before this and leaves a TODO at every site that
// still passed an instance. Those that have been converted no longer need it.
$markersRemoved = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, 'TODO(migration): SF')) {
        continue;
    }

    $lines = explode("\n", $code);
    $kept = [];

    for ($i = 0, $count = count($lines); $i < $count; ++$i) {
        if (!preg_match('/^\s*\/\/ TODO\(migration\): SF\d?\+? takes a form type class-string/', $lines[$i])) {
            $kept[] = $lines[$i];
            continue;
        }

        // Look at the first line after the marker and its continuations.
        $j = $i + 1;

        while ($j < $count && preg_match('/^\s*\/\/ (Move these constructor arguments|and pass them)/', $lines[$j])) {
            ++$j;
        }

        if ($j < $count && str_contains($lines[$j], 'new ')) {
            // Still an instance: keep the marker.
            $kept[] = $lines[$i];
            continue;
        }

        $i = $j - 1;
        ++$markersRemoved;
    }

    $updated = implode("\n", $kept);

    if ($updated !== $code && !$dryRun) {
        file_put_contents($path, $updated);
    }
}

printf("call sites converted:                    %d (in %d files)\n", $converted, $touched);
printf("stale TODO markers removed:              %d\n", $markersRemoved);

if ($unknown !== []) {
    printf("skipped - type not found in src/:        %d\n", array_sum($unknown));
    foreach ($unknown as $name => $count) {
        printf("    %-45s %d\n", $name, $count);
    }
}
