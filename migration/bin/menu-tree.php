<?php

declare(strict_types=1);

/**
 * The sidebar's own tree, with the icon on every node.
 *
 * `nav-inventory.php` answers "which screens does the navigation reach, and who
 * may see them". It groups by the CONTROLLER'S NAMESPACE - `App\Medicine\
 * Controller\` becomes "Medicine" - because that is what a phase needs to know.
 * That grouping is not the menu's own, and the React sidebar inherited it: it
 * renders 18 namespace buckets where the application renders a tree headed
 * "Manage Domain", "Manage Frontend", "HR & Payroll", each with an icon.
 *
 * The inventory also DISCARDS exactly the nodes this needs. An `addChild` with
 * no route is counted as `$labelsOnly` and dropped - and those are the branches,
 * the ones carrying `setAttribute('icon', ...)`.
 *
 * Reading the rendered page instead does not work either, and this is the trap
 * worth recording: the sidebar is filtered by the signed-in user's roles AND by
 * the tenant's enabled modules. Bengal Mart shows 10 top-level items out of a
 * tree far larger, and re-seeding the harness user with all 85 navigation roles
 * still showed 10 - the missing ones are modules this tenant does not have, and
 * no user of this tenant can ever see them. The builder is the only complete
 * source.
 *
 * Parsing it is straightforward for one reason worth stating: every receiver is
 * rooted at `$menu`. The helper methods take the ROOT menu and address their
 * branch by full path - `$menu['Medicine']['Manage Sales']->addChild(...)` -
 * so the ancestry is in the subscripts and no call-graph walk is needed. That
 * is checked, not assumed: the receiver survey is 325 `$menu[_][_]`, 181
 * `$menu[_]`, 39 `$menu[_][_][_]` and 84 with a variable subscript, and the one
 * variable in use is `$business = 'Inventory & Sales'`, a literal.
 *
 * usage: php8.5 migration/bin/menu-tree.php [--out=path.json]
 */

$root = dirname(__DIR__, 2);
$builder = $root . '/next/src/Appearance/Menu/Builder.php';
$out = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

if (!is_file($builder)) {
    fwrite(STDERR, "no builder at {$builder}\n");
    exit(2);
}

$tokens = token_get_all(file_get_contents($builder));
$count = count($tokens);

$text = static fn (int $i): string => is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
$isType = static fn (int $i, int $type): bool => is_array($tokens[$i]) && $tokens[$i][0] === $type;
$skippable = static fn (int $i): bool => is_array($tokens[$i])
    && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);

/** `'Inventory & Sales'` out of `$business = 'Inventory & Sales';`. */
$literals = [];

/** `['miss']` out of `$accounting = ['miss'];`, which module gates test against. */
$listLiterals = [];

for ($i = 0; $i < $count; $i++) {
    if (!$isType($i, T_VARIABLE)) {
        continue;
    }

    $j = $i + 1;
    while ($j < $count && $skippable($j)) {
        $j++;
    }

    if ($j >= $count || $text($j) !== '=') {
        continue;
    }

    $k = $j + 1;
    while ($k < $count && $skippable($k)) {
        $k++;
    }

    if ($k < $count && $isType($k, T_CONSTANT_ENCAPSED_STRING)) {
        // Last assignment wins: the commented-out conditional above the literal
        // is not in the token stream, so there is only ever one.
        $literals[$tokens[$i][1]] = trim($tokens[$k][1], "'\"");

        continue;
    }

    // `$accounting = ['miss'];` - a module gate tests against one of these as
    // often as against an inline list.
    if ($k < $count && ($text($k) === '[' || $isType($k, T_ARRAY))) {
        $body = '';

        for ($n = $k; $n < $count; $n++) {
            if ($text($n) === ';') {
                break;
            }

            $body .= $text($n);
        }

        if (preg_match_all('/[\'"]([A-Za-z][A-Za-z0-9_ -]*)[\'"]/', $body, $am) === 0) {
            continue;
        }

        $listLiterals[$tokens[$i][1]] = $am[1];
    }
}

/**
 * The subscript chain to the left of `->addChild`, read backwards.
 *
 * `$menu['Medicine']['Manage Sales']` yields ['Medicine', 'Manage Sales']. A
 * subscript that is neither a literal nor a known variable makes the whole
 * receiver unusable rather than guessed at - it would silently attach a branch
 * to the wrong parent.
 */
$receiver = static function (int $arrow) use ($tokens, $text, $isType, $skippable, $literals): ?array {
    $i = $arrow - 1;
    $path = [];

    while ($i > 0) {
        while ($i > 0 && $skippable($i)) {
            $i--;
        }

        if ($text($i) === ']') {
            $close = $i;
            $depth = 0;

            for (; $i > 0; $i--) {
                if ($text($i) === ']') {
                    $depth++;
                } elseif ($text($i) === '[') {
                    $depth--;

                    if ($depth === 0) {
                        break;
                    }
                }
            }

            $inner = [];
            for ($k = $i + 1; $k < $close; $k++) {
                if (!$skippable($k)) {
                    $inner[] = $k;
                }
            }

            if (count($inner) !== 1) {
                return null;
            }

            $t = $inner[0];
            if ($isType($t, T_CONSTANT_ENCAPSED_STRING)) {
                array_unshift($path, trim($tokens[$t][1], "'\""));
            } elseif ($isType($t, T_VARIABLE) && isset($literals[$tokens[$t][1]])) {
                array_unshift($path, $literals[$tokens[$t][1]]);
            } else {
                return null;
            }

            $i--;

            continue;
        }

        if ($isType($i, T_VARIABLE)) {
            return $tokens[$i][1] === '$menu' ? $path : null;
        }

        return null;
    }

    return null;
};

/**
 * Which tenant modules a branch is gated behind.
 *
 * The sidebar is filtered twice: by the user's roles, which the inventory
 * already captures, and by the TENANT'S ENABLED MODULES, which nothing captured.
 * That second filter is why Bengal Mart sees 10 top-level nodes out of 40 - it
 * has three modules (Accounting, Miss, Payroll) and the rest of the tree belongs
 * to modules it does not have.
 *
 * The gate is always the same shape, and it is on the CALL, never inside the
 * method - the same trap the role guard on `toolsMenu()` set:
 *
 *     $result = array_intersect($menuName, ['Hospital']);
 *     if (!empty($result)) { ... $menu = $this->HospitalMenu($menu); ... }
 *
 * `$menuName` holds AppModule.moduleClass and `$arrSlugs` holds AppModule.slug,
 * and they are NOT interchangeable - 'Accounting' against 'accounting'. They are
 * kept apart here and compared against their own list on the client.
 */
$functions = [];   // [name, start, end] by token index
$depth = 0;
$fnStack = [];

for ($i = 0; $i < $count; $i++) {
    $t = $text($i);

    if ($isType($i, T_FUNCTION)) {
        $j = $i + 1;
        while ($j < $count && $skippable($j)) {
            $j++;
        }

        if ($isType($j, T_STRING)) {
            $fnStack[] = ['name' => $tokens[$j][1], 'start' => $i, 'depth' => $depth];
        }

        continue;
    }

    if ($t === '{') {
        $depth++;
    } elseif ($t === '}') {
        $depth--;

        if ($fnStack !== [] && end($fnStack)['depth'] === $depth) {
            $fn = array_pop($fnStack);
            $functions[] = ['name' => $fn['name'], 'start' => $fn['start'], 'end' => $i];
        }
    }
}

/** The if-block each `array_intersect` guards, and the modules it tests for. */
$gates = [];

for ($i = 0; $i < $count; $i++) {
    if (!$isType($i, T_STRING) || $tokens[$i][1] !== 'array_intersect') {
        continue;
    }

    $args = '';
    $parens = 0;

    for ($j = $i; $j < $count; $j++) {
        $t = $text($j);
        $args .= $t;

        if ($t === '(') {
            $parens++;
        } elseif ($t === ')') {
            $parens--;

            if ($parens === 0) {
                break;
            }
        }
    }

    $kind = str_contains($args, '$menuName') ? 'modules' : (str_contains($args, '$arrSlugs') ? 'slugs' : null);

    if ($kind === null) {
        continue;
    }

    preg_match_all('/[\'"]([A-Za-z][A-Za-z0-9_ -]*)[\'"]/', $args, $vm);
    $values = $vm[1] ?? [];

    // `array_intersect($arrSlugs, $accounting)` names a variable holding a list.
    if ($values === [] && preg_match('/,\s*(\$\w+)\s*\)/', $args, $varm) === 1) {
        $values = $listLiterals[$varm[1]] ?? [];
    }

    if ($values === []) {
        continue;
    }

    // The block is the next `{` after the test, and everything to its match.
    $open = null;
    for ($j = $j + 1; $j < $count; $j++) {
        if ($text($j) === '{') {
            $open = $j;

            break;
        }

        if ($text($j) === ';' && $open === null) {
            // `$result = array_intersect(...);` - the block is after the `if`.
            continue;
        }
    }

    if ($open === null) {
        continue;
    }

    $d = 0;
    $close = $open;

    for ($j = $open; $j < $count; $j++) {
        $t = $text($j);

        if ($t === '{') {
            $d++;
        } elseif ($t === '}') {
            $d--;

            if ($d === 0) {
                $close = $j;

                break;
            }
        }
    }

    $gates[] = ['kind' => $kind, 'values' => array_values(array_unique($values)), 'start' => $open, 'end' => $close];
}

/** Methods called inside a gate inherit it, and so does everything they declare. */
$methodGates = [];

foreach ($gates as $gate) {
    for ($j = $gate['start']; $j <= $gate['end']; $j++) {
        if (!$isType($j, T_STRING)) {
            continue;
        }

        $prev = $j - 1;
        while ($prev > 0 && $skippable($prev)) {
            $prev--;
        }

        if (!$isType($prev, T_OBJECT_OPERATOR)) {
            continue;
        }

        $next = $j + 1;
        while ($next < $count && $skippable($next)) {
            $next++;
        }

        if ($text($next) !== '(') {
            continue;
        }

        $name = strtolower($tokens[$j][1]);
        $methodGates[$name][$gate['kind']] = array_values(array_unique(
            [...($methodGates[$name][$gate['kind']] ?? []), ...$gate['values']]
        ));
    }
}

/**
 * Which methods `mainMenu` actually reaches.
 *
 * Not every branch in this file is live. `ElectionMenu` is commented out at its
 * only call site - the gate that looks like Election's actually wraps
 * `CommitteeMenu` - so the "Election" node and everything under it is dead, and
 * showing it would put a branch in the rail that the application never renders.
 * `nav-inventory.php` learned this the same way: 67 of the items it first
 * counted were in builder methods nothing calls.
 *
 * The tokeniser drops comments, so a commented-out call is simply not here.
 */
$calls = [];

foreach ($functions as $f) {
    $name = strtolower($f['name']);
    $calls[$name] ??= [];

    for ($j = $f['start']; $j <= $f['end']; $j++) {
        if (!$isType($j, T_STRING)) {
            continue;
        }

        $prev = $j - 1;
        while ($prev > 0 && $skippable($prev)) {
            $prev--;
        }

        if (!$isType($prev, T_OBJECT_OPERATOR)) {
            continue;
        }

        $next = $j + 1;
        while ($next < $count && $skippable($next)) {
            $next++;
        }

        if ($text($next) === '(') {
            $calls[$name][] = strtolower($tokens[$j][1]);
        }
    }
}

/**
 * Reachability, and WHERE in mainMenu's flow each method is called.
 *
 * The rail's order is the order mainMenu builds it, which is not the order the
 * methods appear in the file: `toolsMenu` is declared after `medicineMenu` and
 * called before it, so ordering by declaration puts Tools below Medicine and the
 * rail comes out in an order the application never shows.
 *
 * A method reached from deeper down inherits its top-level call position, so a
 * whole branch stays together wherever its helper happens to be declared.
 */
$reachable = [];
$rank = ['mainmenu' => 0];
$queue = ['mainmenu'];

/** Call sites within a function, as [callee, token index]. */
$callSites = [];

foreach ($functions as $f) {
    $name = strtolower($f['name']);
    $callSites[$name] ??= [];

    for ($j = $f['start']; $j <= $f['end']; $j++) {
        if (!$isType($j, T_STRING)) {
            continue;
        }

        $prev = $j - 1;
        while ($prev > 0 && $skippable($prev)) {
            $prev--;
        }

        if (!$isType($prev, T_OBJECT_OPERATOR)) {
            continue;
        }

        $next = $j + 1;
        while ($next < $count && $skippable($next)) {
            $next++;
        }

        if ($text($next) === '(') {
            $callSites[$name][] = [strtolower($tokens[$j][1]), $j];
        }
    }
}

while ($queue !== []) {
    $fn = array_shift($queue);

    if (isset($reachable[$fn])) {
        continue;
    }

    $reachable[$fn] = true;

    foreach ($callSites[$fn] ?? [] as [$callee, $at]) {
        if (!isset($callSites[$callee]) || isset($reachable[$callee])) {
            continue;
        }

        // From mainMenu, the call site itself. From anywhere else, whatever
        // position in mainMenu's flow led here.
        $rank[$callee] ??= $fn === 'mainmenu' ? $at : ($rank[$fn] ?? 0);
        $queue[] = $callee;
    }
}

$nodes = [];   // path-key => node
$order = [];   // path-key => first-seen index, so the tree keeps the builder's order
$skipped = 0;
$unreachable = 0;

for ($i = 0; $i < $count; $i++) {
    if (!$isType($i, T_STRING) || $tokens[$i][1] !== 'addChild') {
        continue;
    }

    // `->` sits immediately before the method name.
    $arrow = $i - 1;
    while ($arrow > 0 && $skippable($arrow)) {
        $arrow--;
    }

    if (!$isType($arrow, T_OBJECT_OPERATOR)) {
        continue;
    }

    $parent = $receiver($arrow);

    if ($parent === null) {
        $skipped++;

        continue;
    }

    // The call itself, and then everything chained onto it up to the statement
    // end - `setAttribute('icon', ...)` is never inside the addChild parens.
    $call = '';
    $parens = 0;
    $end = $i;

    for ($j = $i; $j < $count; $j++) {
        $t = $text($j);
        $call .= $t;

        if ($t === '(') {
            $parens++;
        } elseif ($t === ')') {
            $parens--;

            if ($parens === 0) {
                $end = $j;

                break;
            }
        }
    }

    if (preg_match('/addChild\(\s*[\'"]([^\'"]+)[\'"]/', $call, $lm) !== 1) {
        $skipped++;

        continue;
    }

    $label = $lm[1];

    // The node that OPENS a branch names it by the same variable its children
    // subscript with - `addChild("{$business}")` against `$menu[$business]`.
    // Left alone it becomes a second top-level node called `{$business}`, beside
    // the real one, holding the icon its children's parent should have had.
    if (preg_match('/^\{?\$(\w+)\}?$/', $label, $vm) === 1 && isset($literals['$' . $vm[1]])) {
        $label = $literals['$' . $vm[1]];
    }

    $route = preg_match('/[\'"]route[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $call, $rm) === 1 ? $rm[1] : null;

    $chain = '';
    for ($j = $end + 1; $j < $count; $j++) {
        $t = $text($j);

        if ($t === ';') {
            break;
        }

        $chain .= $t;
    }

    $icon = preg_match('/setAttribute\(\s*[\'"]icon[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $chain, $im) === 1 ? $im[1] : null;
    $dropdown = str_contains($chain, "'dropdown'") || str_contains($chain, '"dropdown"');

    // What gates this node: the module test around the method that declares it,
    // plus any test it sits directly inside.
    $fn = null;
    foreach ($functions as $f) {
        if ($i > $f['start'] && $i < $f['end'] && ($fn === null || $f['start'] > $fn['start'])) {
            $fn = $f;
        }
    }

    $fnName = $fn === null ? '' : strtolower($fn['name']);

    if ($fn === null || !isset($reachable[$fnName])) {
        $unreachable++;

        continue;
    }

    $gateOn = ['modules' => [], 'slugs' => []];

    if ($fn !== null && isset($methodGates[strtolower($fn['name'])])) {
        foreach ($methodGates[strtolower($fn['name'])] as $kind => $values) {
            $gateOn[$kind] = array_values(array_unique([...$gateOn[$kind], ...$values]));
        }
    }

    foreach ($gates as $gate) {
        if ($i > $gate['start'] && $i < $gate['end']) {
            $gateOn[$gate['kind']] = array_values(array_unique([...$gateOn[$gate['kind']], ...$gate['values']]));
        }
    }

    $path = [...$parent, $label];
    $key = implode("\x1f", $path);

    if (!isset($nodes[$key])) {
        // Ordered by where mainMenu builds this node, not where the file
        // declares it. Nodes written inline in mainMenu order by their own
        // position; everything a helper declares orders by that helper's call.
        $order[$key] = $fnName === 'mainmenu' ? $i : (($rank[$fnName] ?? 0) * 1000000 + ($i % 1000000));

        $nodes[$key] = [
            'label' => $label,
            'path' => $path,
            // A LIST, not one route. Three different routes are declared as
            // "Income" under Accounting > Financial Report - medicine, business
            // and hospital - and which one renders depends on the tenant's
            // enabled modules. Keeping the first silently dropped the other two.
            'routes' => $route !== null ? [$route] : [],
            'icon' => $icon,
            'dropdown' => $dropdown,
            'modules' => $gateOn['modules'],
            'slugs' => $gateOn['slugs'],
        ];

        continue;
    }

    // An item declared twice under different role guards keeps the first, and
    // gains whatever the second knew that the first did not.
    if ($route !== null && !in_array($route, $nodes[$key]['routes'], true)) {
        $nodes[$key]['routes'][] = $route;
    }

    $nodes[$key]['icon'] ??= $icon;
    $nodes[$key]['dropdown'] = $nodes[$key]['dropdown'] || $dropdown;

    // An item declared under two different module gates is visible under either.
    foreach (['modules', 'slugs'] as $kind) {
        $nodes[$key][$kind] = array_values(array_unique([...$nodes[$key][$kind], ...$gateOn[$kind]]));
    }
}

asort($order);

/** Nest by path, creating any parent a child names but nothing declared. */
$tree = [];

foreach (array_keys($order) as $key) {
    $node = $nodes[$key];
    $cursor = &$tree;

    foreach ($node['path'] as $depth => $segment) {
        $cursor[$segment] ??= ['label' => $segment, 'routes' => [], 'icon' => null, 'modules' => [], 'slugs' => [], 'children' => []];

        if ($depth === count($node['path']) - 1) {
            foreach ($node['routes'] as $r) {
                if (!in_array($r, $cursor[$segment]['routes'], true)) {
                    $cursor[$segment]['routes'][] = $r;
                }
            }

            $cursor[$segment]['icon'] = $cursor[$segment]['icon'] ?? $node['icon'];

            // Only on the node that DECLARED them. Unioning a child's gates into
            // its ancestors made Accounting look gated by 'hotel' and
            // 'restaurant', which are inline slug tests on two of its sub-items.
            foreach (['modules', 'slugs'] as $kind) {
                $cursor[$segment][$kind] = array_values(array_unique([...$cursor[$segment][$kind], ...$node[$kind]]));
            }
        }

        $cursor = &$cursor[$segment]['children'];
    }

    unset($cursor);
}

$strip = static function (array $level) use (&$strip): array {
    $list = [];

    foreach ($level as $node) {
        $node['children'] = $strip($node['children']);

        if ($node['children'] === []) {
            unset($node['children']);
        }

        $list[] = $node;
    }

    return $list;
};

$rendered = $strip($tree);

$countNodes = static function (array $level) use (&$countNodes): array {
    $n = 0;
    $withRoute = 0;
    $withIcon = 0;

    foreach ($level as $node) {
        $n++;
        $withRoute += $node['routes'] !== [] ? 1 : 0;
        $withIcon += $node['icon'] !== null ? 1 : 0;
        [$cn, $cr, $ci] = $countNodes($node['children'] ?? []);
        $n += $cn;
        $withRoute += $cr;
        $withIcon += $ci;
    }

    return [$n, $withRoute, $withIcon];
};

[$total, $withRoute, $withIcon] = $countNodes($rendered);

printf(
    "%d top-level nodes, %d nodes in all, %d with a route, %d with an icon (%d receivers not resolvable, %d in methods nothing calls)\n",
    count($rendered),
    $total,
    $withRoute,
    $withIcon,
    $skipped,
    $unreachable
);

foreach ($rendered as $node) {
    printf("  %-26s %-22s %d children\n", $node['label'], $node['icon'] ?? '-', count($node['children'] ?? []));
}

if ($out !== null) {
    file_put_contents($out, json_encode($rendered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo "\nwrote {$out}\n";
}
