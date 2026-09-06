<?php
/**
 * Converts docblock @Route annotations to PHP 8 #[Route] attributes.
 *
 * WHY THIS EXISTS - the failure it repairs was silent, so it is written down:
 * ReportBundle is the only legacy bundle that declared its routes by
 * annotation rather than in a routing.yml (its routing.yml is a single
 * `type: annotation` import of the controller). port-routing.php faithfully
 * rewrites that import to `type: attribute`, because Symfony 7 removed the
 * annotation loader. Nothing, however, rewrote the controller's *docblocks*:
 * Rector's ANNOTATIONS_TO_ATTRIBUTES does not carry a @Route rule, and no
 * other pass looked at them.
 *
 * The result is the worst shape a porting bug can take. Symfony's attribute
 * loader reads the file, finds no attributes, registers nothing, and raises
 * no error - so 70 routes vanished without a single warning. Every template
 * linking to one of them then died at render time, which cost 320 of the 791
 * smoke-tested pages (§11) because `report_dashboard` is linked from
 * Tool/Widget/common.html.twig, included on nearly every backend page.
 *
 * Only one bundle needs this today. It still runs over the whole tree, and
 * still exits non-zero on anything it could not convert, because the class of
 * bug - an annotation nobody translates, failing quietly - is the expensive
 * part, not the one bundle.
 *
 * usage: php migration/bin/port-route-annotations.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) {
    fwrite(STDERR, "usage: port-route-annotations.php <dir> [--dry-run]\n");
    exit(1);
}

const ANNOTATION_FQCN = 'Symfony\\Component\\Routing\\Annotation\\Route';
const ATTRIBUTE_FQCN  = 'Symfony\\Component\\Routing\\Attribute\\Route';

/** Splits an argument list on commas that are not inside a string or a bracket. */
function split_top_level(string $s): array
{
    $parts = [];
    $buf   = '';
    $depth = 0;
    $quote = null;

    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $c = $s[$i];

        if ($quote !== null) {
            $buf .= $c;
            if ($c === '\\' && $i + 1 < $n) { $buf .= $s[++$i]; continue; }
            if ($c === $quote) $quote = null;
            continue;
        }

        if ($c === '"' || $c === "'") { $quote = $c; $buf .= $c; continue; }
        if (str_contains('{[(', $c)) $depth++;
        if (str_contains('}])', $c)) $depth--;

        if ($c === ',' && $depth === 0) { $parts[] = $buf; $buf = ''; continue; }
        $buf .= $c;
    }

    if (trim($buf) !== '') $parts[] = $buf;

    return $parts;
}

/** Doctrine annotation string literal -> its literal value. */
function decode_annotation_string(string $lit): string
{
    $inner = substr($lit, 1, -1);

    return preg_replace('/\\\\(["\\\\])/', '$1', $inner);
}

/**
 * Emits a single-quoted PHP string. Single quotes are deliberate: annotation
 * values are literal, and a route requirement like "\d+" or a path holding a
 * "$" must not be read as a PHP escape or an interpolation.
 */
function php_single_quote(string $value): string
{
    return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
}

/** Doctrine annotation value -> the equivalent PHP expression. */
function convert_value(string $v): string
{
    $v = trim($v);
    if ($v === '') return $v;

    // {..} is a list or a map; PHP wants [..] either way.
    if ($v[0] === '{' && str_ends_with($v, '}')) {
        $out = [];
        foreach (split_top_level(substr($v, 1, -1)) as $part) {
            $part = trim($part);
            if ($part === '') continue;

            // A map entry: "key" = value, or a bare identifier key.
            if (preg_match('/^("(?:[^"\\\\]|\\\\.)*"|[A-Za-z_]\w*)\s*=\s*(.+)$/s', $part, $m)) {
                $key = $m[1][0] === '"'
                    ? php_single_quote(decode_annotation_string($m[1]))
                    : php_single_quote($m[1]);
                $out[] = $key . ' => ' . convert_value($m[2]);
                continue;
            }
            $out[] = convert_value($part);
        }

        return '[' . implode(', ', $out) . ']';
    }

    if ($v[0] === '"') return php_single_quote(decode_annotation_string($v));

    return $v; // number, true/false/null, or an already-quoted literal
}

/** @Route(...) argument list -> a PHP 8 attribute argument list. */
function convert_arguments(string $args): string
{
    $out = [];
    foreach (split_top_level($args) as $arg) {
        $arg = trim($arg);
        if ($arg === '') continue;

        if (preg_match('/^([A-Za-z_]\w*)\s*=\s*(.+)$/s', $arg, $m)) {
            $out[] = $m[1] . ': ' . convert_value($m[2]);
            continue;
        }
        $out[] = convert_value($arg);
    }

    return implode(', ', $out);
}

$files = 0; $converted = 0; $leftovers = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $src  = (string) file_get_contents($path);
    if (!str_contains($src, '@Route')) continue;

    $hits = 0;

    $out = preg_replace_callback(
        '#(?<indent>[ \t]*)/\*\*.*?\*/#s',
        static function (array $m) use (&$hits): string {
            $block  = $m[0];
            $indent = $m['indent'];
            if (!str_contains($block, '@Route')) return $block;

            $attrs = [];
            $stripped = preg_replace_callback(
                '/^[ \t]*\*[ \t]*@Route\((?<args>.*)\)[ \t]*\R?/m',
                static function (array $mm) use (&$attrs, &$hits): string {
                    $hits++;
                    $attrs[] = '#[Route(' . convert_arguments($mm['args']) . ')]';

                    return '';
                },
                $block
            );

            if (!$attrs) return $block;

            // Keep whatever else the docblock carried; drop it if @Route was all.
            $body = preg_replace('#^[ \t]*/\*\*\R?|^[ \t]*\*/\R?#m', '', $stripped);
            $body = trim(preg_replace('/^[ \t]*\*[ \t]?/m', '', $body));

            $attrText = implode("\n", array_map(static fn (string $a): string => $indent . $a, $attrs));

            if ($body === '') return $attrText;

            $rebuilt = $indent . "/**\n";
            foreach (explode("\n", $body) as $l) $rebuilt .= rtrim($indent . ' * ' . $l) . "\n";
            $rebuilt .= $indent . ' */';

            return $rebuilt . "\n" . $attrText;
        },
        $src
    );

    if ($hits === 0) continue;

    // The annotation class is gone in Symfony 8; point the import at the attribute.
    $out = preg_replace(
        '/^use\s+' . preg_quote(ANNOTATION_FQCN, '/') . '\s*;/m',
        'use ' . ATTRIBUTE_FQCN . ';',
        $out,
        -1,
        $importsRewritten
    );

    if (!$importsRewritten && !preg_match('/^use\s+' . preg_quote(ATTRIBUTE_FQCN, '/') . '\s*;/m', $out)) {
        $out = preg_replace('/^(namespace\s+[^;]+;\s*\R)/m', '$1use ' . ATTRIBUTE_FQCN . ";\n", $out, 1);
    }

    if (preg_match('/^[ \t]*\*[ \t]*@Route\b/m', $out)) {
        $leftovers[] = $path;
    }

    $files++;
    $converted += $hits;
    if (!$dryRun) file_put_contents($path, $out);
}

printf("%s %d annotations in %d file(s)\n", $dryRun ? 'would convert' : 'converted', $converted, $files);

if ($leftovers) {
    fwrite(STDERR, "!! @Route left unconverted - routes would be silently dropped:\n");
    foreach ($leftovers as $p) fwrite(STDERR, "   $p\n");
    exit(1);
}
