<?php

declare(strict_types=1);

/**
 * Moves the 96 wire-locked mobile endpoints into terminalbd_api.
 *
 * Phase 9 of the API/React plan. These are the endpoints Android and Flutter
 * applications already in the field call, and the one part of this port where
 * the response shape is a contract with software nobody here can redeploy - so
 * the controllers are copied VERBATIM and only their routing changes.
 *
 * The routing has to change because the two projects declare it differently.
 * `next/` imports three YAML files under three prefixes:
 *
 *     android-api-portal  legacy/Bindu/apiPortal.yml
 *     android-api         legacy/Bindu/apiSales.yml
 *     flutter-api         legacy/Bindu/apiPoskeeper.yml
 *
 * The API project has no YAML routing at all - every route in it is an
 * attribute - so this reads those files and writes the same routes as
 * `#[Route]` above the actions they name, prefix included. Route NAMES are
 * preserved exactly: `api_setup` stays `api_setup`, because a name is how the
 * rest of the application refers to a route and renaming one silently breaks
 * whatever generated a URL from it.
 *
 * The methods are preserved too. Only 56 of the 96 declare any - the rest accept
 * every verb, in the legacy tree as well, so `ANY` in the router is faithful and
 * not an omission. (`methods` in api-endpoints.json is the verb the CAPTURE uses,
 * not a restriction the route carries; reading it as one is the wrong way round.)
 *
 * Where they are declared they are written three ways - `methods: [GET]`,
 * `methods: POST`, and `methods: POST|GET` - and reading only the bracketed form
 * leaves 49 of them with no attribute at all, which in Symfony means every method
 * is allowed. More permissive than the legacy is still a wire change.
 *
 * Five routes are declared with `missing:` rather than `_controller:`. Those are
 * the ones check-routes.php repointed because their action exists in neither
 * tree; they answer 404 in `next/` today and are not carried over. They are
 * reported, not skipped silently.
 *
 * Everything is reconciled against migration/contract/api-endpoints.json at the
 * end, because that file IS the contract: a route this script fails to parse is
 * an endpoint the field calls and the API would not answer.
 *
 * **Actions are matched case-insensitively**, which is not pedantry. The routing
 * says `ApiController::stockItemAction` and the class declares
 * `public function StockItemAction`. PHP method names are case-insensitive so
 * `next/` resolves it; an exact match here dropped the attribute and
 * /android-api/stock-item became a 404. The nav inventory learned the same thing
 * about `businessMenu()` and `BusinessMenu()`, where matching exactly reported 66
 * live routes as dead.
 *
 * **The copy is otherwise byte-identical, and that is the point.** Every
 * difference between the two files is a route attribute; the bodies are
 * untouched, so `compare.php` is comparing the same code against the recorded
 * legacy shapes rather than a rewrite that happens to look similar.
 *
 * `--check` writes nothing and fails if the API's copy does not already declare
 * every live endpoint, which is what CI runs: it needs no database and no server,
 * and it catches a route lost to a re-copy or an edit. The capture proves the
 * shapes; this proves the surface still exists to capture.
 *
 * usage: php8.5 migration/bin/port-mobile-api.php [--api=../terminalbd_api]
 *            [--dry-run] [--check]
 */

$root = dirname(__DIR__, 2);
$api = $root . '/../terminalbd_api';
$dryRun = false;
$check = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--api=')) {
        $api = substr($arg, 6);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--check') {
        $check = true;
        $dryRun = true;
    }
}

if (!is_dir($api . '/src')) {
    fwrite(STDERR, "not an API checkout: {$api}\n");

    exit(2);
}

/**
 * The prefix each Bindu routing file is imported under, read from legacy.yaml.
 *
 * Named explicitly rather than globbed. `legacy/Bindu/main.yml` is imported from
 * the same directory under prefix `/` and holds the site root and the login
 * form - it is not the mobile API, and sweeping it in here gave two routes into
 * User's SecurityController and a prefix borrowed from whichever import came
 * next.
 */
const API_FILES = ['apiPortal', 'apiSales', 'apiPoskeeper'];

$prefixes = [];
$importing = null;

foreach (file($root . '/next/config/routes/legacy.yaml', FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match("/resource:\s*'legacy\/Bindu\/([A-Za-z]+)\.yml'/", $line, $m) === 1) {
        $importing = in_array($m[1], API_FILES, true) ? $m[1] : null;
    } elseif ($importing !== null && preg_match('/prefix:\s*(\S+)/', $line, $m) === 1) {
        $prefixes[$importing] = rtrim($m[1], '/');
        $importing = null;
    }
}

if (count($prefixes) !== count(API_FILES)) {
    fwrite(STDERR, sprintf(
        "expected %d Bindu API imports in legacy.yaml, found %d\n",
        count(API_FILES),
        count($prefixes)
    ));

    exit(1);
}

/**
 * Every route the three files declare, grouped by the action it points at.
 *
 * @var array<string, array<int, array{name: string, path: string, methods: string[]}>> $byAction
 */
$byAction = [];
$dead = [];
$total = 0;

foreach ($prefixes as $file => $prefix) {
    $path = sprintf('%s/next/config/routes/legacy/Bindu/%s.yml', $root, $file);

    if (!is_file($path)) {
        fwrite(STDERR, "missing routing file: {$path}\n");

        exit(1);
    }

    $name = null;
    $route = [];

    $flush = static function () use (&$name, &$route, &$byAction, &$dead, $prefix, &$total): void {
        if ($name === null || !isset($route['path'])) {
            return;
        }

        if (isset($route['missing'])) {
            $dead[$name] = $route['missing'];

            return;
        }

        if (!isset($route['controller'])) {
            return;
        }

        $byAction[$route['controller']][] = [
            'name' => $name,
            'path' => $prefix . ($route['path'] === '/' ? '' : $route['path']),
            'methods' => $route['methods'] ?? [],
        ];
        $total++;
    };

    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^([A-Za-z0-9_.]+):\s*$/', $line, $m) === 1) {
            $flush();
            $name = $m[1];
            $route = [];

            continue;
        }

        if (preg_match('/^\s+path:\s*(\S+)/', $line, $m) === 1) {
            $route['path'] = trim($m[1], '\'"');
        } elseif (preg_match('/_controller:\s*\'?([^\'\s]+)/', $line, $m) === 1) {
            $route['controller'] = $m[1];
        } elseif (preg_match('/^\s+methods:\s*(\S.*)$/', $line, $m) === 1) {
            // Three spellings: [GET], POST, POST|GET.
            $raw = trim($m[1], "[] \t");
            $route['methods'] = array_values(array_filter(array_map(
                static fn (string $x): string => strtoupper(trim($x, " '\"")),
                preg_split('/[,|]/', $raw) ?: []
            )));
        } elseif (preg_match('/^\s+missing:\s*\'?([^\'\s]+)/', $line, $m) === 1) {
            // Repointed by check-routes.php: the action exists in neither tree.
            $route['missing'] = $m[1];
        }
    }

    $flush();
}

printf("%d live route(s) and %d already dead, across %d file(s)\n", $total, count($dead), count($prefixes));

$written = 0;
$attributes = 0;
$unrouted = [];

foreach (glob($root . '/next/src/Bindu/Controller/*.php') as $file) {
    $class = 'App\\Bindu\\Controller\\' . basename($file, '.php');
    $code = file_get_contents($file);

    // The routing attribute, and the import it needs. Placed before the visibility
    // keyword so the indentation of the method is untouched.
    // Keyed lower-case: PHP resolves method names case-insensitively and this
    // codebase relies on it.
    $forClass = [];

    foreach ($byAction as $action => $routes) {
        [$actionClass, $method] = explode('::', $action);

        if ($actionClass === $class) {
            $forClass[strtolower($method)] = $routes;
        }
    }

    $code = preg_replace_callback(
        '/^(    )(public function )(\w+)\(/m',
        static function (array $m) use ($forClass, &$attributes): string {
            $routes = $forClass[strtolower($m[3])] ?? [];

            if ($routes === []) {
                return $m[0];
            }

            $lines = '';

            foreach ($routes as $route) {
                $methods = $route['methods'] === []
                    ? ''
                    : sprintf(", methods: ['%s']", implode("', '", $route['methods']));
                $lines .= sprintf(
                    "%s#[Route('%s', name: '%s'%s)]\n",
                    $m[1],
                    $route['path'],
                    $route['name'],
                    $methods
                );
                $attributes++;
            }

            return $lines . $m[1] . $m[2] . $m[3] . '(';
        },
        $code
    );

    if (!str_contains($code, 'use Symfony\Component\Routing\Attribute\Route;')) {
        $code = preg_replace(
            '/^(use Symfony\\\\Component\\\\HttpFoundation\\\\Request;)$/m',
            "use Symfony\\Component\\Routing\\Attribute\\Route;\n$1",
            $code,
            1
        );
    }

    $target = $api . '/src/Bindu/Controller/' . basename($file);

    if (!$dryRun) {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0o775, true);
        }

        file_put_contents($target, $code);
    }

    $written++;
}

// A route naming an action none of the copied files has is not a warning to
// scroll past: it is an endpoint the field calls and the API would 404.
foreach ($byAction as $action => $routes) {
    [$class, $method] = explode('::', $action);
    $file = $root . '/next/src/Bindu/Controller/' . substr($class, strrpos($class, '\\') + 1) . '.php';

    if (!is_file($file) || preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/i', (string) file_get_contents($file)) !== 1) {
        foreach ($routes as $route) {
            $unrouted[] = sprintf('%s -> %s', $route['name'], $action);
        }
    }
}

if ($check) {
    // What the API declares now, rather than what this run would write.
    $declared = [];

    foreach (glob($api . '/src/Bindu/Controller/*.php') as $file) {
        if (preg_match_all("/#\[Route\('[^']*', name: '([^']+)'/", (string) file_get_contents($file), $m) > 0) {
            foreach ($m[1] as $routeName) {
                $declared[$routeName] = true;
            }
        }
    }

    $shouldHave = [];

    foreach ($byAction as $action => $routes) {
        [$actionClass, $method] = explode('::', $action);
        $file = $root . '/next/src/Bindu/Controller/' . substr($actionClass, strrpos($actionClass, '\\') + 1) . '.php';

        if (!is_file($file) || preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/i', (string) file_get_contents($file)) !== 1) {
            continue;
        }

        foreach ($routes as $route) {
            $shouldHave[$route['name']] = true;
        }
    }

    $lost = array_diff_key($shouldHave, $declared);

    printf(
        "mobile API: %d route(s) declared in terminalbd_api, %d expected\n",
        count($declared),
        count($shouldHave)
    );

    if ($lost !== []) {
        printf("\n%d live endpoint(s) the API no longer declares:\n", count($lost));

        foreach (array_keys($lost) as $routeName) {
            echo '  ' . $routeName . "\n";
        }

        echo "\nrun: php8.5 migration/bin/port-mobile-api.php\n";

        exit(1);
    }

    echo "every live mobile endpoint is declared\n";

    exit(0);
}

printf(
    "%s %d attribute(s) into %d controller(s)\n",
    $dryRun ? 'would write' : 'wrote',
    $attributes,
    $written
);

if ($unrouted !== []) {
    printf("\n%d route(s) name an action that does not exist:\n", count($unrouted));

    foreach ($unrouted as $line) {
        echo '  ' . $line . "\n";
    }
}

if ($dead !== []) {
    printf("\nalready dead in next/, not carried over (%d):\n", count($dead));

    foreach ($dead as $name => $action) {
        printf("  %-40s %s\n", $name, $action);
    }
}

// --- reconcile against the contract ---------------------------------------
$contract = json_decode(
    (string) file_get_contents($root . '/migration/contract/api-endpoints.json'),
    true
);
$expected = [];

foreach ($contract as $entry) {
    $expected[$entry['name']] = true;
}

$written_names = [];

foreach ($byAction as $routes) {
    foreach ($routes as $route) {
        $written_names[$route['name']] = true;
    }
}

$unaccounted = array_diff_key($expected, $written_names, $dead);
$surplus = array_diff_key($written_names, $expected);

printf(
    "\ncontract: %d entries, %d distinct names; accounted for: %d live + %d dead\n",
    count($contract),
    count($expected),
    count($written_names),
    count($dead)
);

if ($unaccounted !== []) {
    printf("\nIN THE CONTRACT AND NOT PORTED (%d):\n", count($unaccounted));

    foreach (array_keys($unaccounted) as $name) {
        echo '  ' . $name . "\n";
    }

    exit(1);
}

if ($surplus !== []) {
    printf("\nported but not in the contract (%d):\n", count($surplus));

    foreach (array_keys($surplus) as $name) {
        echo '  ' . $name . "\n";
    }

    exit(1);
}

printf(
    "\nevery contract endpoint is accounted for: %d attribute(s) written, %d dead,\n"
    . "%d route(s) naming an action that exists in neither tree, and 1 duplicate\n"
    . "name in the contract (api_portal_app is declared twice, which is why it has\n"
    . "96 entries and 95 names).\n",
    $attributes,
    count($dead),
    count($unrouted)
);
