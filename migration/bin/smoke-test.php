<?php

declare(strict_types=1);

/**
 * Requests pages against a real database and reports what breaks.
 *
 * The static checks answer "does this resolve"; this one answers "does it
 * render". It boots the kernel once and drives requests through HttpKernel
 * in-process, so a thousand pages take a minute rather than an hour.
 *
 * Only routes that are safe to call are visited: GET, and neither the path nor
 * the route name may look like it changes something. A legacy application that
 * deletes over GET is normal, so the deny-list is deliberately broad.
 *
 * Needs DATABASE_URL pointing at a database with data - see §11 of the plan.
 *
 * Resumable, because it has to be: legacy controllers call exit; in the middle
 * of an action, which kills the harness with them. Every visit is recorded in a
 * state file, a shutdown handler flushes it, and the next run skips what has
 * already been seen - so repeated runs converge. `--fresh` starts over.
 *
 *   php8.5 migration/bin/smoke-test.php [--limit=N] [--user=username]
 *                                       [--filter=substring] [--report=path]
 *                                       [--state=path] [--fresh]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use App\Kernel;
use App\User\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

$root = dirname(__DIR__, 2);
$options = [
    'limit' => 0,
    'user' => 'migration_check',
    'filter' => '',
    'report' => null,
    'state' => dirname(__DIR__, 2) . '/next/var/smoke-state.json',
];

foreach ($argv as $arg) {
    foreach (array_keys($options) as $name) {
        if (str_starts_with($arg, "--{$name}=")) {
            $options[$name] = substr($arg, strlen($name) + 3);
        }
    }
}

/**
 * Buckets a failure by cause, so the report separates the things worth fixing
 * from the ones already catalogued. Each pattern is checked in order.
 */
const CLASSIFY = [
    'dead route linked from a template (§5.28)' => 'Unable to generate a URL for the named route',
    'dead entity alias (§5.11)' => 'does not exist|Unknown Entity namespace alias',
    'route with no controller (§5.12)' => 'exists in neither the legacy nor the ported tree',
    'this tenant has no config for that module' => 'must be of type App\\\\\w+\\\\Entity\\\\\w*Config',
    // A heuristic, and labelled as one: business_config and dps_config have no
    // rows for this tenant, so every page in those modules walks into a null.
    // Re-check these on a tenant that uses the module before believing them.
    'probably the same - a null the page assumes is there' => 'Call to a member function \\w+\\(\\) on null|on a null variable',
    'expected: the smoke-test user does not have that role' => 'Access Denied',
    'environment: wkhtmltopdf is not installed here' => 'is not executable',
    // The harness sends no query string, so pages built around ?id= or ?term=
    // have nothing to work with. Real coverage of these needs parameters.
    'needs a request parameter the harness does not send' => 'The identifier id is missing|RedirectResponse::__construct\\(\\): Argument #1',
    // Verified against the legacy tree: the template does not exist there
    // either, so the page is a 500 in production too.
    'a template that exists in neither tree' => 'Unable to find template',
    // "e.restaurantConfig =  ORDER BY" - the query is built by interpolating a
    // config that is null for this tenant. Same root cause as the nulls above.
    'DQL built by interpolating a null config' => '\\[Syntax Error\\]',
    // Verified: the method is on a different repository, or on none at all,
    // in the legacy tree as well.
    'calls a repository method that does not exist (pre-existing)' => 'The method name must start with either findBy',
    'queries a field the entity never declared (pre-existing)' => 'Unrecognized field',
    'the barcode dependency cannot be downloaded (§5.32)' => 'CodeItNow',
    'one of the four service ids that exist in neither tree (§5.16)' => 'Controller::get\\(\\)',
    'the controller calls exit; or die()' => 'calls exit; or die',
];

/** Anything that might write. A legacy app deleting over GET is normal. */
const UNSAFE = [
    'delete', 'remove', 'destroy', 'truncate', 'drop', 'purge', 'clear',
    'create', 'new', 'add', 'insert', 'save', 'store', 'update', 'edit',
    'approve', 'reject', 'confirm', 'cancel', 'reverse', 'revert', 'reset',
    'install', 'migrate', 'import', 'upload', 'send', 'sms', 'mail', 'email',
    'pay', 'payment', 'invoice-process', 'generate', 'sync', 'switch', 'login',
    'logout', 'register', 'signup', 'password', 'process', 'status', 'active',
    'inactive', 'publish', 'copy', 'clone', 'merge', 'assign', 'apply', 'post',
];

$isSafe = static function (string $name, string $path): bool {
    $haystack = strtolower($name . ' ' . $path);

    foreach (UNSAFE as $word) {
        if (str_contains($haystack, $word)) {
            return false;
        }
    }

    return true;
};

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Capture the real exception rather than scraping the error page.
$thrown = null;
$container->get('event_dispatcher')->addListener(
    KernelEvents::EXCEPTION,
    static function (ExceptionEvent $event) use (&$thrown): void {
        $thrown ??= $event->getThrowable();
    },
    2048,
);

$user = $container->get('doctrine')->getManager()
    ->getRepository(User::class)
    ->findOneBy(['usernameCanonical' => $options['user']]);

if ($user === null) {
    fwrite(STDERR, sprintf("no such user: %s\n", $options['user']));
    exit(2);
}

// One session, reused across requests, carrying an authenticated token.
$session = new Session(new MockArraySessionStorage());
$session->set('_security_main', serialize(new UsernamePasswordToken($user, 'main', $user->getRoles())));

$routes = $container->get('router')->getRouteCollection();

$state = ['seen' => [], 'failures' => []];

if (!in_array('--fresh', $argv, true) && is_file($options['state'])) {
    $state = json_decode((string) file_get_contents($options['state']), true) ?: $state;

    // A route recorded as pending never came back: the previous run was killed
    // mid-request, by a timeout or a controller that exits hard enough to skip
    // shutdown handlers. Record it and move on, or every run dies in the same
    // place.
    if (($state['pending'] ?? null) !== null) {
        $state['seen'][$state['pending']] = 'aborted';
        $state['pending'] = null;
    }

    printf("resuming: %d pages already visited\n", count($state['seen']));
}

$counts = ['200' => 0, '3xx' => 0, '401' => 0, '403' => 0, '404' => 0, '5xx' => 0, 'exit' => 0, 'aborted' => 0, 'skipped' => 0];

$saveState = static function () use (&$state, $options): void {
    @mkdir(dirname($options['state']), 0o775, true);
    file_put_contents($options['state'], json_encode($state));
};
$failures = $state['failures'];
$visited = 0;
$current = null;

// A controller that calls exit; takes this process with it. Record where we
// were, so the next run skips that route instead of dying on it again.
register_shutdown_function(static function () use (&$state, &$failures, &$current, $options): void {
    if ($current !== null) {
        $name = explode('  ', $current)[0];
        $state['seen'][$name] = 'exit';
        $state['pending'] = null;
        $failures['The controller calls exit; or die() - not reachable through this harness'][] = $current;
    }

    $state['failures'] = $failures;
    @mkdir(dirname($options['state']), 0o775, true);
    file_put_contents($options['state'], json_encode($state));
});

foreach ($routes as $name => $route) {
    $path = $route->getPath();
    $methods = $route->getMethods();

    if ($methods !== [] && !in_array('GET', $methods, true)) {
        ++$counts['skipped'];
        continue;
    }

    if (str_contains($path, '{') || str_starts_with($path, '/_')) {
        ++$counts['skipped'];
        continue;
    }

    // The mobile APIs authenticate on headers this harness does not send, so
    // they answer 401 by design. check-api-contract.php covers them instead.
    if (str_starts_with($path, '/android-api') || str_starts_with($path, '/flutter-api')) {
        ++$counts['skipped'];
        continue;
    }

    if (!$isSafe($name, $path)) {
        ++$counts['skipped'];
        continue;
    }

    if ($options['filter'] !== '' && !str_contains($name, (string) $options['filter'])) {
        ++$counts['skipped'];
        continue;
    }

    if (isset($state['seen'][$name])) {
        $bucket = $state['seen'][$name];
        $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        continue;
    }

    $thrown = null;
    $current = $name . '  ' . $path;

    // Written before the request, so a hard kill still leaves a marker.
    $state['pending'] = $name;
    $saveState();

    $request = Request::create($path, 'GET');
    $request->setSession($session);
    $request->cookies->set($session->getName(), 'smoke-test');

    try {
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);
        $status = $response->getStatusCode();
    } catch (HttpExceptionInterface $e) {
        // A 404 or 403 raised by the controller is an answer, not a failure.
        $thrown = null;
        $status = $e->getStatusCode();
    } catch (Throwable $e) {
        $thrown ??= $e;
        $status = 500;
    }

    ++$visited;
    $current = null;
    $state['pending'] = null;

    $bucket = match (true) {
        $status === 200 => '200',
        $status >= 300 && $status < 400 => '3xx',
        $status === 401 => '401',
        $status === 403 => '403',
        $status === 404 => '404',
        default => '5xx',
    };
    ++$counts[$bucket];
    $state['seen'][$name] = $bucket;

    $state['failures'] = $failures;
    $saveState();

    if ($bucket === '5xx') {
        $reason = $thrown === null
            ? 'HTTP ' . $status
            : $thrown::class . ': ' . preg_replace('/\s+/', ' ', substr($thrown->getMessage(), 0, 200));
        $failures[$reason][] = $name . '  ' . $path;
    }

    if ($options['limit'] > 0 && $visited >= (int) $options['limit']) {
        break;
    }
}

$total = count($state['seen']);
printf("visited %d pages this run, %d in total, as %s\n\n", $visited, $total, $options['user']);

foreach ($counts as $bucket => $count) {
    printf("  %-8s %d\n", $bucket, $count);
}

uasort($failures, static fn (array $a, array $b): int => count($b) <=> count($a));

// Group by cause so the unexplained ones stand out.
$byCause = [];

foreach ($failures as $reason => $pages) {
    $cause = 'unclassified - worth a look';

    foreach (CLASSIFY as $label => $pattern) {
        if (preg_match('/' . $pattern . '/', $reason)) {
            $cause = $label;
            break;
        }
    }

    $byCause[$cause]['pages'] = ($byCause[$cause]['pages'] ?? 0) + count($pages);
    $byCause[$cause]['reasons'][$reason] = $pages;
}

uasort($byCause, static fn (array $a, array $b): int => $b['pages'] <=> $a['pages']);

printf("\nfailures by cause:\n");

foreach ($byCause as $cause => $group) {
    printf("  %5d  %s\n", $group['pages'], $cause);
}

printf("\ndistinct failures: %d\n\n", count($failures));

foreach (array_slice($failures, 0, 25, true) as $reason => $pages) {
    printf("%4d  %s\n        e.g. %s\n", count($pages), $reason, $pages[0]);
}

if ($options['report'] !== null) {
    $out = "# Smoke test\n\nGenerated by `migration/bin/smoke-test.php` against a database with data.\n\n"
        . sprintf("- pages visited: **%d** (GET, no parameters, nothing that looks like it writes)\n", $total)
        . sprintf("- 200: **%d**  3xx: **%d**  401: **%d**  403: **%d**  404: **%d**  5xx: **%d**\n\n",
            $counts['200'], $counts['3xx'], $counts['401'], $counts['403'], $counts['404'], $counts['5xx']);

    $out .= "## By cause\n\n| pages | cause |\n|---:|---|\n";

    foreach ($byCause as $cause => $group) {
        $out .= sprintf("| %d | %s |\n", $group['pages'], $cause);
    }

    $out .= "\n";

    foreach ($byCause as $cause => $group) {
        $out .= sprintf("## %s (%d pages)\n\n", $cause, $group['pages']);

        foreach ($group['reasons'] as $reason => $pages) {
            $out .= sprintf("**%d × %s**\n\n```\n%s\n```\n\n", count($pages), $reason, implode("\n", array_slice($pages, 0, 25)));
        }
    }

    file_put_contents($options['report'], $out);
    printf("\nwrote %s\n", $options['report']);
}
