<?php

declare(strict_types=1);

/**
 * Every SCREEN in the application, not just the ones the navigation reaches.
 *
 * `nav-inventory.php` walks the menu builder and finds 335 screens. That was the
 * agreed scope and it is about 9% of the application: the router has 3,554
 * routes. The rest are reachable - by a link inside a page, by a button, by a
 * URL - and `/domain/globaloption/new` is one of them: a working screen in
 * `next/`, invisible to the navigation, and therefore absent from React.
 *
 * Scope now being "all of it", the inventory has to come from the ROUTER rather
 * than the menu. The distinction that matters is screen or not:
 *
 *   a SCREEN renders a template and answers GET
 *   an ACTION writes and redirects - create, update, delete, approve
 *   a LOOKUP answers JSON for an autocomplete
 *   an EXPORT returns a PDF or a spreadsheet
 *
 * Only the first becomes a React screen. The rest become API endpoints, and
 * counting them as screens would inflate the work by a factor of three.
 *
 * The test is what the action DOES, read from its source: a method whose body
 * reaches `$this->render(` is a screen; one that only redirects, returns a
 * JsonResponse or streams a file is not. Route names lie - `newAction` renders a
 * form, `createAction` does not, and both are "new" to a name matcher.
 *
 * usage: php8.5 migration/bin/route-inventory.php --routes=<debug-router.json> [--out=inventory.json]
 */

$root = dirname(__DIR__, 2);
$routesPath = null;
$out = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--routes=')) {
        $routesPath = substr($arg, 9);
    } elseif (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

if ($routesPath === null || !is_file($routesPath)) {
    fwrite(STDERR, "usage: route-inventory.php --routes=<debug:router --format=json output>\n");
    exit(2);
}

$routes = json_decode((string) file_get_contents($routesPath), true, 512, JSON_THROW_ON_ERROR);

/** Cache of controller file contents, keyed by class. */
$sources = [];

function sourceOf(string $class, string $root, array &$sources): ?string
{
    if (array_key_exists($class, $sources)) {
        return $sources[$class];
    }

    // App\Medicine\Controller\StockController -> next/src/Medicine/Controller/StockController.php
    $relative = str_replace('\\', '/', preg_replace('/^App\\\\/', '', $class) ?? '');
    $path = $root . '/next/src/' . $relative . '.php';
    $sources[$class] = is_file($path) ? (string) file_get_contents($path) : null;

    return $sources[$class];
}

/** The body of one method, by brace counting from its signature. */
function methodBody(string $source, string $method): ?string
{
    if (preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/i', $source, $m, PREG_OFFSET_CAPTURE) !== 1) {
        return null;
    }

    $open = strpos($source, '{', $m[0][1]);

    if ($open === false) {
        return null;
    }

    $depth = 0;

    for ($i = $open, $length = strlen($source); $i < $length; $i++) {
        if ($source[$i] === '{') {
            $depth++;
        } elseif ($source[$i] === '}') {
            $depth--;

            if ($depth === 0) {
                return substr($source, $open, $i - $open + 1);
            }
        }
    }

    return null;
}

$kinds = [];
$screens = [];
$unresolved = 0;

foreach ($routes as $name => $route) {
    $controller = $route['defaults']['_controller'] ?? null;
    $path = $route['path'] ?? '';
    $method = (string) ($route['method'] ?? 'ANY');

    if ($controller === null || !str_contains($controller, '::')) {
        $kinds['no controller'] = ($kinds['no controller'] ?? 0) + 1;

        continue;
    }

    [$class, $action] = explode('::', $controller, 2);

    // A route that cannot be reached by GET is never a screen.
    if ($method !== 'ANY' && !str_contains($method, 'GET')) {
        $kinds['write / action'] = ($kinds['write / action'] ?? 0) + 1;

        continue;
    }

    $source = sourceOf($class, $root, $sources);

    if ($source === null) {
        $unresolved++;
        $kinds['controller not in next/'] = ($kinds['controller not in next/'] ?? 0) + 1;

        continue;
    }

    $body = methodBody($source, $action);

    if ($body === null) {
        $unresolved++;
        $kinds['action not found'] = ($kinds['action not found'] ?? 0) + 1;

        continue;
    }

    $renders = str_contains($body, '$this->render(') || str_contains($body, 'renderView(');
    $json = str_contains($body, 'JsonResponse') || str_contains($body, 'json_encode');
    $file = preg_match('/PHPExcel|Spreadsheet|StreamedResponse|BinaryFileResponse|Dompdf|mpdf|->download\(/i', $body) === 1;
    $redirects = str_contains($body, 'redirectToRoute') || str_contains($body, 'RedirectResponse');

    if ($file) {
        $kind = 'print / export';
    } elseif ($renders && !$json) {
        $kind = 'screen';
    } elseif ($json) {
        $kind = 'lookup / json';
    } elseif ($redirects) {
        $kind = 'write / action';
    } else {
        $kind = 'other';
    }

    $kinds[$kind] = ($kinds[$kind] ?? 0) + 1;

    if ($kind !== 'screen') {
        continue;
    }

    // The module is the controller's namespace, the same attribution
    // nav-inventory.php uses, so both inventories group the same way.
    $module = preg_match('/^App\\\\([A-Za-z]+)\\\\Controller\\\\/', $class, $mm) === 1 ? $mm[1] : '(none)';

    $screens[] = [
        'route' => $name,
        'path' => $path,
        'module' => $module,
        'controller' => $controller,
        // A path with a placeholder needs a real id before it can be rendered,
        // which the harvester cannot invent.
        'parameterised' => str_contains($path, '{'),
    ];
}

printf("%d routes\n", count($routes));

foreach ($kinds as $kind => $count) {
    printf("  %-26s %5d\n", $kind, $count);
}

$byModule = [];
$parameterised = 0;

foreach ($screens as $screen) {
    $byModule[$screen['module']] = ($byModule[$screen['module']] ?? 0) + 1;
    $parameterised += $screen['parameterised'] ? 1 : 0;
}

arsort($byModule);

printf(
    "\n%d screens, %d of them with a parameter in the path (%d renderable without an id)\n\n",
    count($screens),
    $parameterised,
    count($screens) - $parameterised
);

foreach ($byModule as $module => $count) {
    printf("  %-22s %4d\n", $module, $count);
}

if ($out !== null) {
    file_put_contents($out, json_encode($screens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    printf("\nwrote %s\n", $out);
}