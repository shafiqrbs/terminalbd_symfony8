<?php

declare(strict_types=1);

/**
 * Moves a form type's constructor arguments into its options.
 *
 * Runs after port-form-instances.php has rewritten the call sites. For a type
 * that used to be built as `new ItemType($globalOption)`:
 *
 *   - the constructor is removed;
 *   - `globalOption` is declared as an option in configureOptions();
 *   - buildForm() starts by assigning $options['globalOption'] to the same
 *     property the constructor assigned, so the body is untouched.
 *
 * Types then autowire like any other service, which is why the exclusion list
 * in config/services_legacy.yaml can go away.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-form-types.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

function splitArgs(string $args): array
{
    $out = [];
    $depth = 0;
    $buf = '';

    for ($i = 0, $len = strlen($args); $i < $len; ++$i) {
        $ch = $args[$i];

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

function matchPair(string $code, int $openPos, string $open, string $close): int
{
    $depth = 0;

    for ($i = $openPos, $len = strlen($code); $i < $len; ++$i) {
        if ($code[$i] === $open) {
            ++$depth;
        } elseif ($code[$i] === $close) {
            --$depth;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return $len - 1;
}

$stats = ['types converted' => 0, 'options declared' => 0, 'properties declared' => 0, 'skipped' => 0];
$skipped = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!preg_match('/class\s+(\w+)\s+extends\s+(?:AbstractType|\w*FormType)\b/', $code, $cm)) {
        continue;
    }

    if (!preg_match('/(?:public|protected|private)?\s*function\s+__construct\s*\(/', $code, $ctorMatch, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    $ctorStart = $ctorMatch[0][1];
    $sigOpen = strpos($code, '(', $ctorStart);
    $sigEnd = matchPair($code, $sigOpen, '(', ')');
    $params = splitArgs(substr($code, $sigOpen + 1, $sigEnd - $sigOpen - 1));

    if ($params === []) {
        continue;
    }

    $bodyOpen = strpos($code, '{', $sigEnd);
    $bodyEnd = matchPair($code, $bodyOpen, '{', '}');
    $body = substr($code, $bodyOpen + 1, $bodyEnd - $bodyOpen - 1);

    $map = [];

    foreach ($params as $param) {
        if (!preg_match('/\$(\w+)/', $param, $pm)) {
            continue;
        }
        $name = $pm[1];

        // A constructor-promoted parameter is its own property; the promotion
        // goes away with the constructor, so it is re-declared below.
        if (preg_match('/(?:public|protected|private)\s+(?:readonly\s+)?/', $param)) {
            $map[$name] = $name;
            continue;
        }

        $property = preg_match('/\$this->(\w+)\s*=\s*\$' . preg_quote($name, '/') . '\s*;/', $body, $am)
            ? $am[1]
            : $name;
        $map[$name] = $property;
    }

    if ($map === []) {
        ++$stats['skipped'];
        $skipped[] = str_replace($root . '/', '', $path) . ' (no property assignments)';
        continue;
    }

    // 1. Drop the constructor.
    $lineStart = strrpos(substr($code, 0, $ctorStart), "\n") + 1;
    $code = substr($code, 0, $lineStart) . substr($code, $bodyEnd + 1);
    $code = preg_replace('/\n{3,}/', "\n\n", $code);

    // 2. Declare any property the constructor used to create implicitly.
    $missing = [];

    foreach ($map as $property) {
        if (!preg_match('/(?:public|protected|private|var)\s+(?:readonly\s+)?[\w\\\\|?]*\s*\$' . preg_quote($property, '/') . '\s*[;=]/', $code)) {
            $missing[$property] = true;
        }
    }

    if ($missing !== []) {
        $decl = '';
        foreach (array_keys($missing) as $property) {
            $decl .= "    protected \$" . $property . ";\n";
            ++$stats['properties declared'];
        }
        $code = preg_replace('/(class\s+\w+\s+extends\s+[\w\\\\]+\s*(?:implements[^{]+)?\{\n)/', "$1" . $decl . "\n", $code, 1);
    }

    // 3. Assign the options at the top of buildForm().
    if (preg_match('/public function buildForm\s*\(\s*[\w\\\\|?]*\s*\$(\w+)\s*,\s*array\s+\$(\w+)\s*\)\s*:?\s*\w*\s*\n?\s*\{/', $code, $bm, PREG_OFFSET_CAPTURE)) {
        $optionsVar = $bm[2][0];
        $insertAt = $bm[0][1] + strlen($bm[0][0]);
        $assign = "\n";

        foreach ($map as $option => $property) {
            $line = "        \$this->{$property} = \${$optionsVar}['{$option}'];\n";
            if (!str_contains($code, trim($line))) {
                $assign .= $line;
            }
        }

        if (trim($assign) !== '') {
            $code = substr($code, 0, $insertAt) . $assign . substr($code, $insertAt);
        }
    }

    // 4. Declare the options.
    $defaults = [];

    foreach (array_keys($map) as $option) {
        $defaults[] = "'{$option}' => null,";
        ++$stats['options declared'];
    }

    if (preg_match('/public function configureOptions\s*\([^)]*\$(\w+)\s*\)\s*:?\s*\w*\s*\n?\s*\{/', $code, $om, PREG_OFFSET_CAPTURE)) {
        $resolverVar = $om[1][0];
        $insertAt = $om[0][1] + strlen($om[0][0]);
        $block = "\n        \$" . $resolverVar . "->setDefaults([\n";

        foreach ($defaults as $default) {
            $block .= "            {$default}\n";
        }

        $block .= "        ]);\n";

        if (!preg_match("/\\\${$resolverVar}->setDefaults\(\[\s*'" . preg_quote(array_key_first($map), '/') . "'/", $code)) {
            $code = substr($code, 0, $insertAt) . $block . substr($code, $insertAt);
        }
    } else {
        $block = "\n    public function configureOptions(OptionsResolver \$resolver): void\n    {\n        \$resolver->setDefaults([\n";

        foreach ($defaults as $default) {
            $block .= "            {$default}\n";
        }

        $block .= "        ]);\n    }\n";
        $lastBrace = strrpos($code, '}');
        $code = substr($code, 0, $lastBrace) . $block . substr($code, $lastBrace);

        if (!str_contains($code, 'use Symfony\Component\OptionsResolver\OptionsResolver;')) {
            $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse Symfony\\Component\\OptionsResolver\\OptionsResolver;\n", $code, 1);
        }
    }

    ++$stats['types converted'];

    if (!$dryRun) {
        file_put_contents($path, $code);
    }
}

foreach ($stats as $label => $count) {
    printf("%-22s %d\n", $label, $count);
}

foreach (array_slice($skipped, 0, 20) as $line) {
    echo "  skipped: {$line}\n";
}
