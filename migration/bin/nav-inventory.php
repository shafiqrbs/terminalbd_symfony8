<?php

declare(strict_types=1);

/**
 * Inventories the navigation tree, which is the scope of the React port.
 *
 * The decision taken was to port what the nav reaches rather than every route:
 * of 3,462 routes, 419 have no controller in either tree and many more are
 * reached only by a link inside a page. `Appearance\Menu\Builder` is 2,146
 * lines and the single place a user's navigation is declared, so it is both the
 * scope and the checklist.
 *
 * Read statically rather than by booting the menu: the builder branches on the
 * signed-in user's roles and this tenant's modules, so any one boot shows one
 * tenant's slice. The static read is the union - which is the scope.
 *
 * Each item is resolved to its route, controller and module, so the report can
 * say what a module actually owes: screens, and the endpoints behind them.
 *
 * usage: php8.5 migration/bin/nav-inventory.php [--report=path]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
$report = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--report=')) {
        $report = substr($arg, strlen('--report='));
    }
}

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$routes = [];

foreach ($kernel->getContainer()->get('router')->getRouteCollection() as $name => $route) {
    $controller = (string) $route->getDefault('_controller');
    $routes[$name] = [
        'path' => $route->getPath(),
        'module' => preg_match('/^App\\\\([A-Za-z]+)\\\\Controller\\\\/', $controller, $m) ? $m[1] : null,
        'methods' => $route->getMethods() ?: ['ANY'],
    ];
}

$builder = $root . '/next/src/Appearance/Menu/Builder.php';
$src = (string) file_get_contents($builder);

/**
 * The roles named by one `if` condition, as a group.
 *
 * Within a group the builder writes `or` / `||`, so any one of them is enough;
 * groups nest, so all of them must hold. `getRole() === 'ROLE_SUPER_ADMIN'` is
 * the one condition written differently, and it guards the whole admin tree.
 */
function rolesInCondition(string $condition): array
{
    preg_match_all(
        "/isGranted\(\s*'([A-Z_]+)'|getRole\(\)\s*===?\s*'([A-Z_]+)'/",
        $condition,
        $m,
        PREG_SET_ORDER
    );

    $roles = [];

    foreach ($m as $hit) {
        $role = $hit[1] !== '' ? $hit[1] : ($hit[2] ?? '');

        if ($role !== '') {
            $roles[] = $role;
        }
    }

    return array_values(array_unique($roles));
}

/**
 * Every addChild, with its label, its route, and the role conditions in scope.
 *
 * Tokenised rather than pattern-matched across lines: the guards are nested `if`
 * blocks wrapping groups of items, so which roles apply to an item is decided by
 * where its braces sit, and a regex over the file cannot see that. A menu item
 * with no group is visible to any authenticated user.
 */
$tokens = token_get_all($src);
$depth = 0;
$stack = [];       // ['depth' => int, 'roles' => string[]] - AND across entries
$pending = null;   // roles from a condition whose block has not opened yet
$scopes = [];      // route => string[][], the guards written around the item
$owner = [];       // route => the method that declares it
$callSites = [];   // method => [['caller' => string, 'groups' => string[][]]]
$functions = [];   // ['depth' => int, 'name' => string]
$commented = [];   // route => label, for items that exist only inside comments
$items = [];
$labelsOnly = 0;
$count = count($tokens);

/** The guard groups currently in scope, innermost last. */
$currentGroups = static function (array $stack): array {
    $groups = [];

    foreach ($stack as $frame) {
        if ($frame['roles'] !== []) {
            $groups[] = $frame['roles'];
        }
    }

    return $groups;
};

for ($i = 0; $i < $count; $i++) {
    $token = $tokens[$i];

    if (is_array($token)) {
        if ($token[0] === T_IF || $token[0] === T_ELSEIF) {
            $condition = '';
            $parens = 0;

            for ($j = $i; $j < $count; $j++) {
                $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                $condition .= $text;

                if ($text === '(') {
                    $parens++;
                } elseif ($text === ')') {
                    $parens--;

                    if ($parens === 0) {
                        $i = $j;

                        break;
                    }
                }
            }

            $roles = rolesInCondition($condition);
            $pending = $roles === [] ? [] : $roles;

            continue;
        }

        if ($token[0] === T_ELSE) {
            // The negative branch of a role check is not a role check.
            $pending = [];

            continue;
        }

        if ($token[0] === T_STRING && $token[1] === 'addChild') {
            $call = '';
            $parens = 0;

            for ($j = $i; $j < $count; $j++) {
                $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                $call .= $text;

                if ($text === '(') {
                    $parens++;
                } elseif ($text === ')') {
                    $parens--;

                    if ($parens === 0) {
                        break;
                    }
                }
            }

            if (preg_match('/addChild\(\s*[\'"]([^\'"]+)[\'"]/', $call, $lm) !== 1) {
                continue;
            }

            $label = $lm[1];
            $route = preg_match('/[\'"]route[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $call, $rm) === 1
                ? $rm[1]
                : '';

            if ($route === '') {
                $labelsOnly++;

                continue;
            }

            // An item reached under two different guards is visible under either,
            // so the narrower reading would hide it. Keep the first.
            $items[$route] ??= ['label' => $label, 'route' => $route];
            $scopes[$route] ??= $currentGroups($stack);
            $owner[$route] ??= $functions === [] ? '' : strtolower($functions[array_key_last($functions)]['name']);

            continue;
        }

        if ($token[0] === T_FUNCTION) {
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $pendingFunction = $tokens[$j][1];

                    break;
                }
            }

            continue;
        }

        if ($token[0] === T_OBJECT_OPERATOR) {
            // `$menu = $this->toolsMenu($menu);` - the guard on the CALL is the
            // guard on everything the method declares. The whole Tools tree is
            // super-admin only, and says so nowhere inside toolsMenu(). The
            // caller is recorded too, so a guard composes down a chain of calls
            // and a method nothing calls can be told from one that is guarded.
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                }

                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && ($tokens[$j + 1] ?? null) === '(') {
                    // Lower-cased throughout: PHP method names are
                    // case-insensitive, and this file declares businessMenu()
                    // and calls BusinessMenu(). Matching them exactly made 66
                    // live routes look like dead code.
                    $callSites[strtolower($tokens[$j][1])][] = [
                        'caller' => $functions === [] ? '' : strtolower($functions[array_key_last($functions)]['name']),
                        'groups' => $currentGroups($stack),
                    ];
                }

                break;
            }

            continue;
        }

        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
            // Menu items that were commented out. Counting these as screens is
            // what an earlier regex over the file did, and it inflated the scope
            // by 20 - including the whole of Customer. They are listed in the
            // report rather than dropped silently: a commented-out item is a
            // product decision somebody took, and may be one to revisit.
            preg_match_all(
                '/addChild\(\s*[\'"]([^\'"]+)[\'"].*?[\'"]route[\'"]\s*=>\s*[\'"]([a-zA-Z0-9_]+)[\'"]/s',
                $token[1],
                $cm,
                PREG_SET_ORDER
            );

            foreach ($cm as $hit) {
                $commented[$hit[2]] ??= $hit[1];
            }

            continue;
        }

        if ($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
            $depth++;
        }

        continue;
    }

    if ($token === '{') {
        $depth++;

        if (($pendingFunction ?? null) !== null) {
            $functions[] = ['depth' => $depth, 'name' => $pendingFunction];
            $pendingFunction = null;
        }

        if ($pending !== null) {
            $stack[] = ['depth' => $depth, 'roles' => $pending];
            $pending = null;
        }

        continue;
    }

    if ($token === '}') {
        while ($stack !== [] && $stack[array_key_last($stack)]['depth'] === $depth) {
            array_pop($stack);
        }

        while ($functions !== [] && $functions[array_key_last($functions)]['depth'] === $depth) {
            array_pop($functions);
        }

        $depth--;

        continue;
    }

    if ($token === ';') {
        // `if (...) $x->addChild(...);` - a guard with no block guards one
        // statement, which has already been handled by the time we get here.
        $pending = null;
    }
}

foreach (array_keys($items) as $route) {
    unset($commented[$route]);
}

/**
 * The menus the framework itself asks for. Everything else in this file is only
 * navigation if something reachable from one of these calls it - `DrugMenu()` is
 * 40 lines of menu that nothing has called since it was written.
 */
$entryPoints = [];
$menuConfig = $root . '/next/config/services_menus.yaml';

if (is_file($menuConfig)) {
    preg_match_all(
        "/'App\\\\Appearance\\\\Menu\\\\Builder'\s*,\s*'([A-Za-z0-9_]+)'/",
        (string) file_get_contents($menuConfig),
        $em
    );
    $entryPoints = array_fill_keys(array_map('strtolower', $em[1]), true);
}

if ($entryPoints === []) {
    $entryPoints = ['mainmenu' => true, 'footermenu' => true, 'megamenu' => true];
}

/**
 * The guard on a method: what its call site requires, plus what its caller's own
 * guard requires, all the way up to an entry point. Returns null when nothing
 * reachable calls it, which is not the same as "no guard".
 *
 * A method called from two places under different guards is reachable under
 * either, and this structure - AND of ORs - cannot say "either". It widens to a
 * single choice of every role involved, which errs towards showing a menu item
 * rather than hiding one.
 */
$guardOf = static function (string $method) use (&$guardOf, $callSites, $entryPoints, &$visiting): ?array {
    if ($method === '') {
        return null;
    }

    if (isset($entryPoints[$method])) {
        return [];
    }

    if (isset($visiting[$method])) {
        return null; // a cycle is not a path from an entry point
    }

    $visiting[$method] = true;
    $reachable = [];

    foreach ($callSites[$method] ?? [] as $site) {
        $callerGuard = $guardOf($site['caller']);

        if ($callerGuard === null) {
            continue;
        }

        $groups = $callerGuard;

        foreach ($site['groups'] as $group) {
            if (!in_array($group, $groups, true)) {
                $groups[] = $group;
            }
        }

        $reachable[] = $groups;
    }

    unset($visiting[$method]);

    if ($reachable === []) {
        return null;
    }

    if (count($reachable) === 1) {
        return $reachable[0];
    }

    $widened = [];

    foreach ($reachable as $groups) {
        foreach ($groups as $group) {
            foreach ($group as $role) {
                $widened[$role] = true;
            }
        }
    }

    return $widened === [] ? [] : [array_keys($widened)];
};

$unreachable = [];

foreach ($scopes as $route => $groups) {
    $method = $owner[$route] ?? '';
    $inherited = isset($entryPoints[$method]) ? [] : $guardOf($method);

    if ($inherited === null) {
        $unreachable[$route] = ['label' => $items[$route]['label'], 'method' => $method];
        unset($items[$route], $scopes[$route]);

        continue;
    }

    $merged = $inherited;

    foreach ($groups as $group) {
        if (!in_array($group, $merged, true)) {
            $merged[] = $group;
        }
    }

    $scopes[$route] = $merged;
}

$byModule = [];
$unresolved = [];

foreach ($items as $route => $item) {
    $known = $routes[$route] ?? null;

    if ($known === null) {
        $unresolved[] = $route;

        continue;
    }

    $module = $known['module'] ?? '(none)';
    $byModule[$module][] = $item + $known + ['roles' => $scopes[$route] ?? []];
}

ksort($byModule);

$screens = 0;

foreach ($byModule as $list) {
    $screens += count($list);
}

printf(
    "navigation items: %d linking to a route, %d headings/dropdowns\n"
    . "  resolve to a controller: %d in %d modules\n"
    . "  named route does not exist: %d\n",
    count($items),
    $labelsOnly,
    $screens,
    count($byModule),
    count($unresolved)
);

foreach ($byModule as $module => $list) {
    printf("  %-20s %3d screens\n", $module, count($list));
}

if ($report !== null) {
    $out = "# React port scope: what the navigation reaches\n\n"
        . "Generated by `migration/bin/nav-inventory.php`.\n\n"
        . "The scope of the React port is what a user can navigate to, not every route:\n"
        . "3,462 routes exist, 419 of them have no controller in either tree, and most of the\n"
        . "rest are reached by a link inside a page rather than from the menu. This is the\n"
        . "checklist a module is measured against.\n\n"
        . "Read statically from `Appearance\\Menu\\Builder`, because the builder branches on the\n"
        . "signed-in user's roles and the tenant's enabled modules - booting it shows one\n"
        . "tenant's slice, and the union is what has to be built.\n\n"
        . "Two kinds of declaration are excluded and listed at the end: items inside comments,\n"
        . "and items in builder methods nothing calls. Both look like navigation when the file\n"
        . "is read line by line, and neither is reachable by any user.\n\n"
        . "The fourth column is the role guard an item sits under: `|` is a choice within one\n"
        . "`if`, `AND` is a nested one, and `-` means any authenticated user sees it. Read from\n"
        . "the braces rather than the lines, which is why this is tokenised.\n\n"
        . sprintf(
            "- navigation items linking to a route: **%d**\n"
            . "- headings and dropdown parents (not screens): %d\n"
            . "- resolving to a controller: **%d** across **%d** modules\n"
            . "- naming a route that does not exist: %d\n\n",
            count($items),
            $labelsOnly,
            $screens,
            count($byModule),
            count($unresolved)
        )
        . "| module | screens |\n|---|---:|\n";

    foreach ($byModule as $module => $list) {
        $out .= sprintf("| %s | %d |\n", $module, count($list));
    }

    $out .= sprintf("| **total** | **%d** |\n\n", $screens);

    foreach ($byModule as $module => $list) {
        usort($list, static fn (array $a, array $b): int => $a['label'] <=> $b['label']);
        $out .= "## {$module}\n\n```\n";

        foreach ($list as $i) {
            $guard = $i['roles'] === []
                ? '-'
                : implode(' AND ', array_map(
                    static fn (array $group): string => implode('|', $group),
                    $i['roles']
                ));
            $out .= sprintf("%-42s %-38s %-52s %s\n", $i['label'], $i['route'], $i['path'], $guard);
        }

        $out .= "```\n\n";
    }

    if ($unreachable !== []) {
        $byMethod = [];

        foreach ($unreachable as $route => $u) {
            $byMethod[$u['method']][] = sprintf('%s (%s)', $u['label'], $route);
        }

        ksort($byMethod);
        $out .= sprintf(
            "## Menu items in methods nothing calls\n\n"
            . "%d items across %d builder methods. Each method is live code - not commented out\n"
            . "- but nothing reachable from `mainMenu`, `megaMenu` or `footerMenu` calls it, so no\n"
            . "user can navigate to any of it. `businessMenu()` looks identical to these and is\n"
            . "NOT here: it is called as `BusinessMenu()`, and PHP does not care about the case.\n\n",
            count($unreachable),
            count($byMethod)
        );

        foreach ($byMethod as $method => $labels) {
            sort($labels);
            $out .= sprintf("- `%s()` - %d: %s\n", $method, count($labels), implode(', ', $labels));
        }

        $out .= "\n";
    }

    if ($commented !== []) {
        ksort($commented);
        $resolvable = 0;

        foreach (array_keys($commented) as $route) {
            if (isset($routes[$route])) {
                $resolvable++;
            }
        }

        $out .= sprintf(
            "## Menu items commented out in the builder\n\n"
            . "%d items are declared inside comments, %d of which resolve to a controller. They\n"
            . "are not navigation - nothing reaches them - so they are not screens this port\n"
            . "owes. Read statically they look exactly like live ones, which is how an earlier\n"
            . "count of this file reported %d screens instead of %d. Several of the routes still\n"
            . "exist and still work; only the menu entry is gone.\n\n```\n",
            count($commented),
            $resolvable,
            $screens + $resolvable,
            $screens
        );

        foreach ($commented as $route => $label) {
            $out .= sprintf("%-42s %s\n", $label, $route);
        }

        $out .= "```\n\n";
    }

    if ($unresolved !== []) {
        sort($unresolved);
        $out .= "## Menu items naming a route that does not exist\n\n"
            . "Dead in this tree. Check each against the legacy tree before treating it as a\n"
            . "product decision - that is the mistake §5.46 records.\n\n```\n"
            . implode("\n", $unresolved) . "\n```\n";
    }

    file_put_contents($report, $out);
    printf("wrote %s\n", $report);

    // The same inventory as data. The React route table is generated from this
    // rather than from the report's fixed-width columns, because a role guard is
    // a nested structure and a column is not.
    $json = preg_replace('/\.md$/', '.json', $report);
    $payload = [];

    foreach ($byModule as $module => $list) {
        usort($list, static fn (array $a, array $b): int => $a['label'] <=> $b['label']);

        foreach ($list as $i) {
            $payload[$module][] = [
                'label' => $i['label'],
                'route' => $i['route'],
                'path' => $i['path'],
                'roles' => $i['roles'],
            ];
        }
    }

    file_put_contents(
        $json,
        json_encode(
            ['screens' => $screens, 'modules' => $payload],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) . "\n"
    );
    printf("wrote %s\n", $json);
}
