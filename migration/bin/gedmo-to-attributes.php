<?php
/**
 * Converts @Gedmo\* docblock annotations to PHP 8 attributes.
 *
 * Rector has no Gedmo rules, and Doctrine ORM 3 dropped the AnnotationDriver
 * entirely - so any @Gedmo left in a docblock is silently ignored at runtime,
 * which would quietly break Timestampable/Blameable/Slug/Tree on 187 entities.
 *
 * usage: php migration/bin/gedmo-to-attributes.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: gedmo-to-attributes.php <dir> [--dry-run]\n"); exit(1); }

$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() === 'php') $files[] = $f->getPathname();
}

$changedFiles = 0; $converted = 0; $manual = [];

foreach ($files as $path) {
    $src = (string) file_get_contents($path);
    if (!str_contains($src, '@Gedmo\\')) continue;

    $out = preg_replace_callback('#(?<indent>[ \t]*)/\*\*.*?\*/#s', function (array $m) use (&$converted, &$manual, $path) {
        $block  = $m[0];
        $indent = $m['indent'];
        if (!str_contains($block, '@Gedmo\\')) return $block;

        $attrs = [];
        // Peel the delimiters BEFORE the " * " decoration - otherwise the closing
        // "*/" is reduced to a bare "/" and survives into the rebuilt block.
        $flat = preg_replace('#^[ \t]*/\*\*#', '', $block);
        $flat = preg_replace('#\*/[ \t]*$#', '', $flat);
        $flat = preg_replace('/^[ \t]*\*[ \t]?/m', '', $flat);

        $keptLines = [];
        $i = 0; $len = strlen($flat);
        $plain = '';
        while ($i < $len) {
            if (!preg_match('/\G@Gedmo\\\\(\w+)/', $flat, $mm, 0, $i)) { $plain .= $flat[$i]; $i++; continue; }
            $name = $mm[1];
            $i += strlen($mm[0]);
            $args = '';
            if ($i < $len && $flat[$i] === '(') { $args = readBalanced($flat, $i); }
            try {
                $attrs[] = '#[Gedmo\\' . $name . ($args === '' ? '' : '(' . convertArgs($args, $name) . ')') . ']';
                $converted++;
            } catch (RuntimeException $e) {
                $manual[] = "$path: @Gedmo\\$name - " . $e->getMessage();
                $plain .= '@Gedmo\\' . $name . $args; // leave it alone
            }
            // swallow a trailing comma/newline left behind
            while ($i < $len && in_array($flat[$i], [',', ' ', "\t"], true)) $i++;
        }
        if (!$attrs) return $block;

        // rebuild the docblock without the Gedmo lines
        $body = array_values(array_filter(
            array_map('rtrim', explode("\n", trim($plain, "\n"))),
            fn($l) => trim($l) !== ''
        ));
        $rebuilt = '';
        if ($body) {
            $rebuilt = $indent . "/**\n";
            foreach ($body as $l) $rebuilt .= $indent . ' * ' . $l . "\n";
            $rebuilt .= $indent . ' */';
        }
        $attrText = implode("\n", array_map(fn($a) => $indent . $a, $attrs));
        return $rebuilt === '' ? $attrText : $rebuilt . "\n" . $attrText;
    }, $src);

    if ($out !== $src) {
        $changedFiles++;
        if (!$dryRun) file_put_contents($path, $out);
    }
}

printf("%s %d annotations across %d files\n", $dryRun ? 'would convert' : 'converted', $converted, $changedFiles);
if ($manual) {
    printf("\nNEEDS MANUAL REVIEW (%d):\n", count($manual));
    foreach (array_unique($manual) as $x) echo "  $x\n";
}

// --- helpers ---------------------------------------------------------------

/** Reads a (...) or {...} group starting at $i, advancing $i past it. Returns the group incl. delimiters. */
function readBalanced(string $s, int &$i): string
{
    $open = $s[$i];
    $close = $open === '(' ? ')' : '}';
    $depth = 0; $start = $i; $inStr = false; $q = '';
    for (; $i < strlen($s); $i++) {
        $c = $s[$i];
        if ($inStr) { if ($c === $q && $s[$i-1] !== '\\') $inStr = false; continue; }
        if ($c === '"' || $c === "'") { $inStr = true; $q = $c; continue; }
        if ($c === $open) $depth++;
        elseif ($c === $close) { $depth--; if ($depth === 0) { $i++; return substr($s, $start, $i - $start); } }
    }
    throw new RuntimeException('unbalanced group');
}

/** Splits "a=1, b={x,y}" on top-level commas only. */
function splitTop(string $s): array
{
    $parts = []; $buf = ''; $depth = 0; $inStr = false; $q = '';
    for ($i = 0; $i < strlen($s); $i++) {
        $c = $s[$i];
        if ($inStr) { $buf .= $c; if ($c === $q && $s[$i-1] !== '\\') $inStr = false; continue; }
        if ($c === '"' || $c === "'") { $inStr = true; $q = $c; $buf .= $c; continue; }
        if ($c === '(' || $c === '{') $depth++;
        if ($c === ')' || $c === '}') $depth--;
        if ($c === ',' && $depth === 0) { $parts[] = trim($buf); $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $parts[] = trim($buf);
    return $parts;
}

function convertArgs(string $paren, string $owner): string
{
    $inner = trim(substr($paren, 1, -1));
    if ($inner === '') return '';
    $out = [];
    foreach (splitTop($inner) as $part) {
        if (!preg_match('/^(\w+)\s*=\s*(.*)$/s', $part, $m)) {
            throw new RuntimeException("unparsed argument: $part");
        }
        $out[] = $m[1] . ': ' . convertValue(trim($m[2]), $m[1]);
    }
    return implode(', ', $out);
}

function convertValue(string $v, string $key): string
{
    if ($v === '') return "''";
    if (str_starts_with($v, '{')) {
        $items = splitTop(trim(substr($v, 1, -1)));
        // options={@Gedmo\SlugHandlerOption(name="x", value="y")} -> ['x' => 'y']
        if ($items && str_starts_with(trim($items[0]), '@Gedmo\\SlugHandlerOption')) {
            $pairs = [];
            foreach ($items as $it) {
                if (!preg_match('/name\s*=\s*"([^"]*)"\s*,\s*value\s*=\s*(.+?)\s*\)\s*$/s', $it, $mm)) {
                    throw new RuntimeException("unparsed SlugHandlerOption: $it");
                }
                $pairs[] = "'" . $mm[1] . "' => " . convertValue(trim($mm[2]), 'value');
            }
            return '[' . implode(', ', $pairs) . ']';
        }
        // handlers={@Gedmo\SlugHandler(...)} -> [new Gedmo\SlugHandler(...)]
        if ($items && str_starts_with(trim($items[0]), '@Gedmo\\SlugHandler')) {
            $hs = [];
            foreach ($items as $it) {
                $it = trim($it);
                if (!preg_match('/^@Gedmo\\\\(\w+)/', $it, $mm)) throw new RuntimeException("unparsed handler: $it");
                $j = strlen($mm[0]);
                $args = readBalanced($it, $j);
                $hs[] = 'new Gedmo\\' . $mm[1] . '(' . convertArgs($args, $mm[1]) . ')';
            }
            return '[' . implode(', ', $hs) . ']';
        }
        // plain list: {"a","b"}
        return '[' . implode(', ', array_map(fn($x) => convertScalar(trim($x)), $items)) . ']';
    }
    // SlugHandler class="Gedmo\...\TreeSlugHandler" -> ::class so it survives a rename
    if ($key === 'class' && preg_match('/^"([\w\\\\]+)"$/', $v, $m)) {
        return '\\' . ltrim($m[1], '\\') . '::class';
    }
    return convertScalar($v);
}

function convertScalar(string $v): string
{
    if (preg_match('/^"(.*)"$/s', $v, $m)) return "'" . str_replace("'", "\\'", $m[1]) . "'";
    if (in_array(strtolower($v), ['true', 'false', 'null'], true)) return strtolower($v);
    if (is_numeric($v)) return $v;
    throw new RuntimeException("unrecognised scalar: $v");
}
