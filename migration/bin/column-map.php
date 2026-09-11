<?php

declare(strict_types=1);

/**
 * What a Twig list template calls each of its columns, and what it reads into it.
 *
 * Pairing a screen's column headers with entity properties by NAME does not
 * work, and the failures are quiet ones. "Avg PP" is `averagePurchasePrice` and
 * no fuzzy rule reaches that; a rule loose enough to try pairs "Stock Qnt" with
 * `stockName` and "Sales Qnt" with `salesPrice`, which renders the wrong number
 * under the right heading and looks entirely plausible. `app:suggest-fields`
 * offered `Payment -> paymentMobile` and `Purchase -> purchasePrice` on that
 * basis.
 *
 * The template already says. `<th>Avg PP</th>` and the cell beneath it reads
 * `{{ entity.averagePurchasePrice }}`, so the mapping is there to be read rather
 * than guessed - the same reason the geometry was measured off the running app
 * instead of grepped out of six stylesheets.
 *
 * Headers and cells are paired POSITIONALLY within the table, which is what
 * makes this reliable and also where it stops being reliable: a template whose
 * columns are conditional (`{% if config.showRack %}<th>Rack</th>{% endif %}`)
 * has more headers in the source than the screen renders, and the alignment
 * drifts. Those are reported as UNCERTAIN rather than guessed at, because a
 * confidently wrong mapping is the thing this exists to avoid.
 *
 *   php8.5 migration/bin/column-map.php <template.html.twig> [--json]
 *   php8.5 migration/bin/column-map.php --harvest=<parity.json> [--out=map.json]
 *
 * The second form matches every screen in a parity harvest to the template that
 * renders it, by comparing the harvested column headers against each template's
 * own. The controller-to-template mapping is not needed and would not help: 21
 * of these templates are named the Symfony 2 way through a variable, which is
 * what port-template-aliases.php exists for.
 */

/** @return list<array{text: string, conditional: bool}> */
function cells(string $html, string $tag): array
{
    preg_match_all('#<' . $tag . '\b[^>]*>(.*?)</' . $tag . '>#si', $html, $m, PREG_SET_ORDER);
    $out = [];

    foreach ($m as $cell) {
        $inner = $cell[1];
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#\{[%\{].*?[%\}]\}#s', ' ', $inner) ?? '')) ?? '');

        $out[] = [
            'text' => $text,
            'raw' => $inner,
            // A cell whose content depends on a condition is not safely
            // positional: the rendered table may not have it at all.
            'conditional' => str_contains($inner, '{% if') || str_contains($inner, '{%if'),
        ];
    }

    return $out;
}

/** @return array{template: string, alignable: bool, columns: list<array{label: string, properties: list<string>, certain: bool}>}|null */
function mapTemplate(string $path, string $root): ?array
{
    $source = @file_get_contents($path);

    if ($source === false || preg_match('#<thead\b.*?>(.*?)</thead>#si', $source, $headMatch) !== 1) {
        return null;
    }

    $headSource = $headMatch[1];
    $after = substr($source, (int) strpos($source, $headMatch[0]) + strlen($headMatch[0]));

    if (preg_match('#<tbody\b.*?>(.*?)</tbody>#si', $after, $bodyMatch) !== 1) {
        return null;
    }

    $headers = cells($headSource, 'th');
    $bodyCells = cells($bodyMatch[1], 'td');
    $conditionalHeaders = str_contains($headSource, '{% if') || str_contains($headSource, '{%if');
    $pairs = [];

    foreach ($headers as $i => $header) {
        $cell = $bodyCells[$i] ?? null;
        $expressions = [];

        if ($cell !== null) {
            preg_match_all('/\b(?:entity|row|item)\.([a-zA-Z][a-zA-Z0-9_.]*)/', $cell['raw'], $em);
            $expressions = array_values(array_unique($em[1] ?? []));
        }

        $pairs[] = [
            'label' => $header['text'],
            'properties' => $expressions,
            'certain' => !$conditionalHeaders && $cell !== null && !$cell['conditional'] && count($expressions) === 1,
        ];
    }

    return [
        'template' => str_replace($root . '/', '', $path),
        'alignable' => !$conditionalHeaders && count($headers) === count($bodyCells),
        'columns' => $pairs,
    ];
}

$args = array_slice($argv, 1);
$root = dirname(__DIR__, 2);
$json = in_array('--json', $args, true);
$harvest = null;
$out = null;
$path = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--harvest=')) {
        $harvest = substr($arg, 10);
    } elseif (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    } elseif (!str_starts_with($arg, '--')) {
        $path = $arg;
    }
}

/** Compare a header and a property name on the same footing. */
$norm = static fn (string $v): string => strtolower(preg_replace('/[^a-z0-9]+/i', '', $v) ?? '');

if ($harvest !== null) {
    if (!is_file($harvest)) {
        fwrite(STDERR, "no harvest at {$harvest}\n");
        exit(2);
    }

    $screens = json_decode((string) file_get_contents($harvest), true, 512, JSON_THROW_ON_ERROR);

    $templates = [];
    $directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/next/templates'));

    foreach ($directory as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.html.twig')) {
            continue;
        }

        $mapped = mapTemplate($file->getPathname(), $root);

        if ($mapped !== null && $mapped['alignable']) {
            $templates[] = $mapped;
        }
    }

    $map = [];
    $matched = 0;
    $certainColumns = 0;

    foreach ($screens as $screen) {
        $route = $screen['route'] ?? null;
        $columns = $screen['twig']['columns'] ?? [];

        if ($route === null || ($screen['twigBroken'] ?? false) === true || $columns === []) {
            continue;
        }

        $wanted = [];

        foreach ($columns as $label) {
            $n = $norm((string) $label);

            if ($n !== '' && !in_array($n, ['action', 'actions'], true)) {
                $wanted[$n] = (string) $label;
            }
        }

        if ($wanted === []) {
            continue;
        }

        // The template that renders this screen is the one whose headers cover
        // the ones the screen actually showed. Matching on the rendered headers
        // rather than on the controller means a template named through a
        // variable is found like any other.
        $best = null;
        $bestScore = 0;
        $module = (string) ($screen['module'] ?? '');

        foreach ($templates as $template) {
            $have = [];

            foreach ($template['columns'] as $column) {
                $have[$norm($column['label'])] = true;
            }

            $score = 0;

            foreach (array_keys($wanted) as $n) {
                $score += isset($have[$n]) ? 1 : 0;
            }

            // A screen's own module breaks ties. Accounting's vendor list and
            // Business's have the same three headers - Name, Mobile, Address -
            // so header overlap alone picked whichever was seen first, and the
            // template chosen decides which properties are read.
            if ($module !== '' && stripos($template['template'], '/' . $module . '/') !== false) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $template;
            }
        }

        // Every column the screen showed has to be in the template, or this is
        // a different screen that happens to share some headings.
        if ($best === null || $bestScore < count($wanted)) {
            continue;
        }

        $pairs = [];

        foreach ($best['columns'] as $column) {
            $n = $norm($column['label']);

            if (!isset($wanted[$n]) || !$column['certain']) {
                continue;
            }

            $pairs[$wanted[$n]] = $column['properties'][0];
            $certainColumns++;
        }

        if ($pairs === []) {
            continue;
        }

        $matched++;
        $map[$route] = ['template' => $best['template'], 'columns' => $pairs];
    }

    if ($out !== null) {
        file_put_contents($out, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    printf(
        "%d screens matched to a template, %d columns mapped with certainty%s\n",
        $matched,
        $certainColumns,
        $out !== null ? " -> {$out}" : ''
    );

    exit(0);
}

if ($path === null) {
    fwrite(STDERR, "usage: column-map.php <template.html.twig> [--json] | --harvest=<parity.json> [--out=map.json]\n");
    exit(2);
}

if (!is_file($path)) {
    $candidate = $root . '/' . ltrim($path, '/');
    $path = is_file($candidate) ? $candidate : $path;
}

$mapped = mapTemplate($path, $root);

if ($mapped === null) {
    fwrite(STDERR, "no table in {$path}\n");
    exit(1);
}

if ($json) {
    echo json_encode($mapped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";

    exit(0);
}

printf("%s\n  %s\n\n", $mapped['template'], $mapped['alignable'] ? 'positions align' : 'HEADERS ARE CONDITIONAL, positions are not reliable');

foreach ($mapped['columns'] as $pair) {
    printf(
        "  %-22s %-42s %s\n",
        $pair['label'] === '' ? '(blank)' : $pair['label'],
        implode(', ', $pair['properties']) ?: '-',
        $pair['certain'] ? '' : '?'
    );
}
