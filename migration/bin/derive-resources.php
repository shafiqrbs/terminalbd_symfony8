<?php

declare(strict_types=1);

/**
 * Draft an ApiResource for every screen that has no resource yet.
 *
 * Scope is now the whole application: 1,459 screens against 329 built. The
 * screens themselves are generated - scaffold-screens.mjs does that from a
 * harvest - but every one needs a resource behind it, and there are 284 of
 * those, all written by hand from the controller they replace.
 *
 * The controller says what a resource needs, in a shape that repeats:
 *
 *     $entities = $em->getRepository(Expenditure::class)->findWithSearch($this->getUser(), $data);
 *     $pagination = $this->paginate($entities);
 *     return $this->render('Accounting/Expenditure/index.html.twig', ['entities' => $pagination, ...]);
 *
 * which is entity, queryMethod and queryArgs - precisely the fields an
 * ApiResource declares. This reads them out.
 *
 * IT DRAFTS, IT DOES NOT DECIDE. Every automated mapping attempted in this port
 * has been wrong in a way that looked right: name matching paired "Sales Qnt"
 * with `salesPrice`, template alignment paired "Avg PP" with
 * `averageSalesPrice`, and both were confident. A wrong resource is worse than
 * either - it serves the wrong rows under the right heading, or 500s. So:
 *
 *   - only where the render's list variable traces to exactly ONE repository
 *     call. Two candidates means the screen shows two lists and a person picks.
 *   - only where the entity class resolves to a real file.
 *   - roles are NOT derived here at all. This was originally documented as
 *     "left empty, which the registry treats as deny", and that was wrong:
 *     ResourceController::guard() loops over the role list, so an empty one
 *     restricts nothing. Generating resources from these drafts without a guard
 *     would have published every one of them to any signed-in user.
 *     app:generate-resources establishes the guard and refuses to emit without
 *     one.
 *
 * usage: php8.5 migration/bin/derive-resources.php --inventory=<route-inventory.json> [--out=draft.json]
 */

$root = dirname(__DIR__, 2);
$inventoryPath = null;
$out = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--inventory=')) {
        $inventoryPath = substr($arg, 12);
    } elseif (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

if ($inventoryPath === null || !is_file($inventoryPath)) {
    fwrite(STDERR, "usage: derive-resources.php --inventory=<route-inventory.json>\n");
    exit(2);
}

$screens = json_decode((string) file_get_contents($inventoryPath), true, 512, JSON_THROW_ON_ERROR);
$sources = [];

function controllerSource(string $class, string $root, array &$sources): ?string
{
    if (array_key_exists($class, $sources)) {
        return $sources[$class];
    }

    $relative = str_replace('\\', '/', preg_replace('/^App\\\\/', '', $class) ?? '');
    $path = $root . '/next/src/' . $relative . '.php';
    $sources[$class] = is_file($path) ? (string) file_get_contents($path) : null;

    return $sources[$class];
}

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

/**
 * What the controller hands a repository, in the vocabulary ApiResource uses.
 *
 * These are the same five the hand-written resources pass, and they mean
 * something specific: `config` is the tenant's config for THIS module, not the
 * tenant, and a Medicine query scoped by GlobalOption would show another
 * tenant's stock.
 */
function argumentName(string $argument, array $locals = [], ?string &$configKey = null): ?string
{
    $argument = trim($argument);

    // A local standing in for one of them: `$globalOption = $user->getGlobalOption();`
    // and `$config = $user->getGlobalOption()->getMedicineConfig();` are the two
    // commonest lines in these controllers, and every module names its config
    // something different - $hospital, $inventory, $medicine.
    if (isset($locals[$argument])) {
        $argument = $locals[$argument];
    }

    return match (true) {
        (bool) preg_match('/getUser\(\)$/', $argument) => 'user',
        $argument === '$user' => 'user',
        (bool) preg_match('/^\$(data|_REQUEST|request)$/', $argument) => 'search',
        (bool) preg_match('/getGlobalOption\(\)$/', $argument) => 'tenant',
        (bool) preg_match('/get([A-Za-z]*Config)\(\)$/', $argument, $cm) => (static function () use (&$configKey, $cm): string {
            // WHICH config, not just that it is one. ApiResource defaults
            // configKey to medicineConfig, so a DPS screen asking for `config`
            // was handed the medicine config - null for this tenant - and the
            // repository refused it as a type error.
            $configKey = lcfirst($cm[1]);

            return 'config';
        })(),
        (bool) preg_match('/getGlobalOption\(\)->getId\(\)$/', $argument) => 'tenantId',
        (bool) preg_match('/get[A-Za-z]*Config\(\)->getId\(\)$/', $argument) => 'configId',
        (bool) preg_match("/^'[^']*'$/", $argument) => 'literal:' . trim($argument, "'"),
        (bool) preg_match('/^\d+$/', $argument) => 'literal:' . $argument,
        default => null,
    };
}

$drafts = [];
$skips = [];

foreach ($screens as $screen) {
    if ($screen['parameterised']) {
        $skips['needs a record id'] = ($skips['needs a record id'] ?? 0) + 1;

        continue;
    }

    [$class, $action] = explode('::', $screen['controller'], 2);
    $source = controllerSource($class, $root, $sources);

    if ($source === null) {
        $skips['no controller source'] = ($skips['no controller source'] ?? 0) + 1;

        continue;
    }

    $body = methodBody($source, $action);

    if ($body === null) {
        $skips['action not found'] = ($skips['action not found'] ?? 0) + 1;

        continue;
    }

    // Every `getRepository(X::class)->method(args)` in the action. A list screen
    // has one that feeds the table and several that fill dropdowns; only the
    // first is the resource, and telling them apart is what the render's own
    // variable name does below.
    preg_match_all(
        '/\$(\w+)\s*=\s*(?:\$em|\$this->em|\$this->getDoctrine\(\)->getManager\(\))'
        . '->getRepository\(\s*\\\\?([A-Za-z0-9_\\\\]+)::class\s*\)->(\w+)\(([^;]*)\)\s*;/',
        $body,
        $calls,
        PREG_SET_ORDER
    );

    if ($calls === []) {
        $skips['no repository call'] = ($skips['no repository call'] ?? 0) + 1;

        continue;
    }

    // The variable the template iterates. `paginate($entities)` is the tell.
    $listVariable = null;

    if (preg_match('/\$(\w+)\s*=\s*\$this->paginate\(\s*\$(\w+)/', $body, $pm) === 1) {
        $listVariable = $pm[2];
    } elseif (preg_match("/'entities'\s*=>\s*\\\$(\w+)/", $body, $em2) === 1) {
        $listVariable = $em2[1];
    }

    if ($listVariable === null) {
        $skips['no list variable'] = ($skips['no list variable'] ?? 0) + 1;

        continue;
    }

    $matching = array_values(array_filter($calls, static fn (array $c): bool => $c[1] === $listVariable));

    if (count($matching) !== 1) {
        $skips[count($matching) === 0 ? 'list variable is not a repository call' : 'several calls build the list'] =
            ($skips[count($matching) === 0 ? 'list variable is not a repository call' : 'several calls build the list'] ?? 0) + 1;

        continue;
    }

    [, , $entity, $method, $arguments] = $matching[0];

    $entityFile = $root . '/next/src/' . str_replace('\\', '/', preg_replace('/^App\\\\/', '', $entity) ?? '') . '.php';

    if (!is_file($entityFile)) {
        $skips['entity class not found'] = ($skips['entity class not found'] ?? 0) + 1;

        continue;
    }

    $parsed = [];
    $unknown = false;
    $configKey = null;

    // What each local in this action was assigned, so an argument that is a
    // variable can be read as the expression behind it.
    $locals = [];

    if (preg_match_all('/\$(\w+)\s*=\s*([^;]+);/', $body, $assignments, PREG_SET_ORDER) > 0) {
        foreach ($assignments as $assignment) {
            $locals['$' . $assignment[1]] = trim($assignment[2]);
        }
    }

    foreach (array_filter(array_map('trim', explode(',', $arguments)), static fn (string $a): bool => $a !== '') as $argument) {
        $name = argumentName($argument, $locals, $configKey);

        if ($name === null) {
            $unknown = true;

            break;
        }

        $parsed[] = $name;
    }

    if ($unknown) {
        $skips['an argument could not be named'] = ($skips['an argument could not be named'] ?? 0) + 1;

        continue;
    }

    preg_match("/render\(\s*'([^']+)'/", $body, $tm);

    $drafts[] = [
        'route' => $screen['route'],
        'path' => $screen['path'],
        'module' => $screen['module'],
        'entity' => ltrim($entity, '\\'),
        'queryMethod' => $method,
        'queryArgs' => $parsed,
        'template' => $tm[1] ?? null,
        'configKey' => $configKey,
    ];
}

printf("%d screens in the inventory\n  %d drafted\n", count($screens), count($drafts));

arsort($skips);

foreach ($skips as $reason => $count) {
    printf("  %-38s %5d skipped\n", $reason, $count);
}

$byModule = [];

foreach ($drafts as $draft) {
    $byModule[$draft['module']] = ($byModule[$draft['module']] ?? 0) + 1;
}

arsort($byModule);
echo "\n";

foreach ($byModule as $module => $count) {
    printf("  %-22s %4d\n", $module, $count);
}

if ($out !== null) {
    file_put_contents($out, json_encode($drafts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    printf("\nwrote %s\n", $out);
}
