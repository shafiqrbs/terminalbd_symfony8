<?php

declare(strict_types=1);

/**
 * Requests the actions both other harnesses refuse to: delete, approve, reverse.
 *
 * This is where §5.34 said the remaining defects would be. 300 broken DELETE and
 * UPDATE statements were found by *reading* the tree, not by running it, because
 * every sweep so far has stopped at the deny-list - and a statement ORM 3 rejects
 * outright cannot be found any other way than by executing it.
 *
 * SAFETY, which is the whole design:
 *
 *   - Every request runs inside a transaction that is rolled back. The
 *     statements still execute, so a DELETE with a legacy alias in it still
 *     throws; only the commit is skipped. There is no --commit here, and there
 *     should not be.
 *   - Nested transactions use savepoints, so an action that opens and commits
 *     its own transaction is still discarded by the outer rollback.
 *   - Before anything destructive runs, the rollback is proved on this
 *     connection: a marker row is written, rolled back, and its absence checked.
 *     If that does not hold the run aborts, because everything after it depends
 *     on the guarantee.
 *   - It refuses to start without --confirm, and prints the database it is
 *     pointed at, because the cost of running this against the wrong one is not
 *     symmetric with the cost of typing a flag.
 *
 * Resumable like the others: actions call exit; and take the process with them.
 *
 *   php8.5 migration/bin/destructive-test.php --confirm [--limit=N]
 *          [--user=username] [--filter=substring] [--report=path]
 *          [--state=path] [--fresh]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';
require_once __DIR__ . '/route-values.php';

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
    'limit'  => 0,
    'user'   => 'migration_check',
    'filter' => '',
    'report' => null,
    'state'  => $root . '/next/var/destructive-state.json',
];

foreach ($argv as $arg) {
    foreach (array_keys($options) as $name) {
        if (str_starts_with($arg, "--{$name}=")) {
            $options[$name] = substr($arg, strlen($name) + 3);
        }
    }
}

if (!in_array('--confirm', $argv, true)) {
    fwrite(STDERR, "This harness requests delete and approve actions. Every one is rolled\n");
    fwrite(STDERR, "back, but point it at a database you can afford to be wrong about.\n");
    fwrite(STDERR, "Re-run with --confirm.\n");
    exit(2);
}

/** The words that make an action destructive - the inverse of the other harnesses' deny-list. */
const DESTRUCTIVE = [
    'delete', 'remove', 'destroy', 'truncate', 'drop', 'purge', 'reset',
    'approve', 'reject', 'cancel', 'reverse', 'revert', 'revoke', 'discard',
    'clear', 'wipe', 'merge', 'assign',
];

/**
 * Actions that would reach outside the database, where a rollback saves nothing.
 * A sent SMS cannot be un-sent.
 */
const OFF_LIMITS = ['sms', 'mail', 'email', 'notify', 'send', 'payment', 'refund', 'backup', 'restore'];

$classify = static function (?Throwable $e): string {
    if ($e === null) {
        return 'unclassified - worth a look';
    }

    $message = $e->getMessage();

    return match (true) {
        $e instanceof Symfony\Component\Security\Core\Exception\AccessDeniedException
            => 'expected: the harness user does not have that role',
        $e instanceof Symfony\Component\Routing\Exception\RouteNotFoundException
            || str_contains($message, 'Unable to generate a URL for the named route')
            => 'redirects to a route that does not exist (§5.28)',
        str_contains($message, 'argument that could not be resolved')
            || (str_contains($message, 'identifier') && str_contains($message, 'is missing'))
            => 'HARNESS: the id it filled does not resolve to that entity',
        str_contains($message, 'Unrecognized field')
            => 'queries a field the entity never declared (pre-existing)',
        str_contains($message, 'has no field or association named')
            => 'DQL names a field the entity does not have',
        str_contains($message, 'is not defined') || str_contains($message, 'Class') && str_contains($message, 'does not exist')
            => 'DQL names a class that does not exist (§5.11 / §5.43)',
        str_contains($message, '[Syntax Error]') || str_contains($message, '[Semantical Error]')
            => 'DQL that ORM 3 rejects outright (§5.34)',
        str_contains($message, 'foreign key constraint')
            => 'a foreign key the delete does not clear first',
        str_contains($message, 'cannot be null')
            => 'a NOT NULL column the action nulls',
        str_contains($message, 'Unknown column')
            => 'a column the mapping declares and the database does not',
        str_contains($message, 'EntityManager is closed')
            => 'the entity manager was closed by an earlier failure',
        str_contains($message, 'Call to a member function') && str_contains($message, 'on null')
            => 'a null the action assumes is there',
        str_contains($message, 'must be of type')
            => 'a type the action assumes it was given',
        default => 'unclassified - worth a look',
    };
};

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$thrown = null;
$container->get('event_dispatcher')->addListener(
    KernelEvents::EXCEPTION,
    static function (ExceptionEvent $event) use (&$thrown): void {
        $thrown ??= $event->getThrowable();
    },
    2048,
);

$registry = $container->get('doctrine');
$em = $registry->getManager();
$connection = $registry->getConnection();
$connection->setNestTransactionsWithSavepoints(true);

printf("database: %s\n", $connection->getDatabase() ?? '(unknown)');

// ---- prove the rollback before trusting it --------------------------------

$marker = 'harness-rollback-probe-' . bin2hex(random_bytes(4));
$connection->beginTransaction();
$connection->executeStatement(
    'INSERT INTO user_group (name, roles) VALUES (?, ?)',
    [$marker, serialize([])]
);
$inside = (int) $connection->fetchOne('SELECT COUNT(*) FROM user_group WHERE name = ?', [$marker]);
$connection->rollBack();
$after = (int) $connection->fetchOne('SELECT COUNT(*) FROM user_group WHERE name = ?', [$marker]);

if ($inside !== 1 || $after !== 0) {
    fwrite(STDERR, sprintf(
        "rollback does not hold on this connection (seen inside: %d, after: %d) - refusing to run\n",
        $inside,
        $after
    ));
    exit(3);
}

printf("rollback verified: a row written inside a transaction is gone after it\n\n");

$user = $em->getRepository(User::class)->findOneBy(['usernameCanonical' => $options['user']]);

if ($user === null) {
    fwrite(STDERR, sprintf("no such user: %s\n", $options['user']));
    exit(2);
}

$session = new Session(new MockArraySessionStorage());
$session->set('_security_main', serialize(new UsernamePasswordToken($user, 'main', $user->getRoles())));

$values = new RouteValues($em, $user->getGlobalOption()?->getId());
$routes = $container->get('router')->getRouteCollection();

$state = ['seen' => [], 'failures' => [], 'pending' => null];

if (!in_array('--fresh', $argv, true) && is_file($options['state'])) {
    $state = json_decode((string) file_get_contents($options['state']), true) ?: $state;

    if (($state['pending'] ?? null) !== null) {
        $state['seen'][$state['pending']] = 'exit';
        $state['pending'] = null;
    }

    printf("resuming: %d action(s) already requested\n", count($state['seen']));
}

$counts = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'exit' => 0, 'skipped' => 0];
$failures = $state['failures'];
$current = null;
$requested = 0;

$saveState = static function () use (&$state, &$failures, $options): void {
    $state['failures'] = $failures;
    @mkdir(dirname((string) $options['state']), 0o775, true);
    file_put_contents($options['state'], json_encode($state));
};

register_shutdown_function(static function () use (&$state, &$failures, &$current, $saveState): void {
    if ($current !== null) {
        $state['seen'][$current] = 'exit';
        $state['pending'] = null;
        $failures['The action calls exit; or die() - not reachable through this harness'][] = $current;
    }

    $saveState();
});

foreach ($routes as $name => $route) {
    if ($options['limit'] > 0 && $requested >= (int) $options['limit']) {
        break;
    }

    $path = $route->getPath();
    $haystack = strtolower($name . ' ' . $path);

    $destructive = false;

    foreach (DESTRUCTIVE as $word) {
        if (str_contains($haystack, $word)) {
            $destructive = true;
            break;
        }
    }

    $offLimits = false;

    foreach (OFF_LIMITS as $word) {
        if (str_contains($haystack, $word)) {
            $offLimits = true;
            break;
        }
    }

    if (!$destructive
        || $offLimits
        || str_starts_with($path, '/_')
        || str_starts_with($path, '/android-api')
        || str_starts_with($path, '/flutter-api')
        || str_contains((string) $route->getDefault('_controller'), 'UnresolvableRouteController')
        || ($options['filter'] !== '' && !str_contains($name, (string) $options['filter']))
    ) {
        ++$counts['skipped'];
        continue;
    }

    if (isset($state['seen'][$name])) {
        $bucket = $state['seen'][$name];
        $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        continue;
    }

    $filled = $values->fill($name, $route);

    if ($filled === null) {
        ++$counts['skipped'];
        continue;
    }

    $methods = $route->getMethods();
    $method = $methods === [] ? 'GET' : (in_array('GET', $methods, true) ? 'GET' : $methods[0]);

    $thrown = null;
    $current = $name;
    $state['pending'] = $name;
    $saveState();

    $connection = $registry->getConnection();
    $connection->setNestTransactionsWithSavepoints(true);
    $connection->beginTransaction();

    try {
        $request = Request::create($filled, $method);
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'destructive-test');
        $request->headers->set('referer', 'http://localhost' . $path);

        $status = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false)->getStatusCode();
    } catch (HttpExceptionInterface $e) {
        $thrown = null;
        $status = $e->getStatusCode();
    } catch (Throwable $e) {
        $thrown ??= $e;
        $status = 500;
    }

    $current = null;
    $state['pending'] = null;
    ++$requested;

    $bucket = match (true) {
        $status >= 500 => '5xx',
        $status >= 400 => '4xx',
        $status >= 300 => '3xx',
        default        => '2xx',
    };

    $state['seen'][$name] = $bucket;
    $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;

    if ($bucket === '5xx') {
        $label = $classify($thrown);
        $detail = $thrown === null
            ? 'HTTP 500 with no exception captured'
            : $thrown::class . ': ' . $thrown->getMessage();
        $failures[$label][] = $name . '  ' . $filled . "\n      " . substr($detail, 0, 220);
    }

    try {
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    } catch (Throwable) {
        // The connection went with the failure; MySQL has discarded the
        // transaction already and the next request opens a new one.
    }

    if (!$registry->getManager()->isOpen()) {
        $registry->resetManager();
    }

    $saveState();
}

$saveState();

printf("\nrequested %d destructive action(s) this run, all rolled back\n", $requested);

foreach ($counts as $label => $n) {
    printf("  %-8s %d\n", $label, $n);
}

if ($options['report'] === null) {
    return;
}

$total = array_sum(array_intersect_key($counts, array_flip(['2xx', '3xx', '4xx', '5xx', 'exit'])));

$lines = [
    '# Destructive test',
    '',
    'Generated by `migration/bin/destructive-test.php`. Delete, approve and',
    'reverse actions, each requested inside a transaction that is rolled back.',
    'Actions that reach outside the database - sms, mail, payment - are skipped,',
    'because a rollback does not cover them.',
    '',
    sprintf('- actions requested: **%d**', $total),
    sprintf('- 2xx: **%d**  3xx: **%d**  4xx: **%d**  5xx: **%d**  exit: **%d**',
        $counts['2xx'], $counts['3xx'], $counts['4xx'], $counts['5xx'], $counts['exit']),
    '',
    '## By cause',
    '',
    '| actions | cause |',
    '|---:|---|',
];

uasort($failures, static fn (array $a, array $b): int => count($b) <=> count($a));

foreach ($failures as $label => $items) {
    $lines[] = sprintf('| %d | %s |', count($items), $label);
}

foreach ($failures as $label => $items) {
    $lines[] = '';
    $lines[] = sprintf('## %s (%d)', $label, count($items));
    $lines[] = '';
    $lines[] = '```';

    foreach (array_slice($items, 0, 40) as $item) {
        $lines[] = $item;
    }

    if (count($items) > 40) {
        $lines[] = sprintf('... and %d more', count($items) - 40);
    }

    $lines[] = '```';
}

file_put_contents($options['report'], implode("\n", $lines) . "\n");
printf("wrote %s\n", $options['report']);
