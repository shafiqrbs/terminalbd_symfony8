<?php

declare(strict_types=1);

/**
 * Submits the forms the application renders, and reports what breaks.
 *
 * smoke-test.php answers "does this page render". This one answers "does
 * saving work", which no read-only sweep can reach: §5.34's 300 broken DELETE
 * and UPDATE statements fire on save, and §5.39 - a user written with a null
 * username_canonical and an unhashed password - was found by one person
 * clicking one button, after four sweeps had passed the same code.
 *
 * It renders each page, parses every <form> out of it, synthesises a value for
 * every field, and posts it back through the kernel in the same process. That
 * matters for more than speed: the form's CSRF token is rendered as a stateless
 * double-submit token, which a browser's Stimulus controller rewrites before
 * submit - a token posted by curl is rejected, and the templates render only
 * per-field errors, so the rejection shows up as a page that looks fine. In
 * process, the token the page rendered is the token the request carries, and it
 * validates.
 *
 * SAFETY. Every submission runs inside a transaction that is rolled back, so
 * the sweep can be repeated and leaves nothing behind. The statements still
 * execute - constraint violations, broken DQL and missing columns all surface;
 * only the commit is skipped. --commit turns that off, which is worth doing
 * once against a database you can throw away, because a controller that reads
 * back what it wrote in a later request will not see it under rollback.
 *
 * Destructive actions are skipped by name, on the same deny-list smoke-test.php
 * uses. A legacy application that deletes over GET is normal here, so it is
 * deliberately broad.
 *
 * Resumable, because it has to be: controllers call exit; in the middle of an
 * action and take the harness with them. Every form is recorded before it is
 * submitted, and the next run skips what has been seen. --fresh starts over.
 *
 *   php8.5 migration/bin/write-test.php [--limit=N] [--user=username]
 *                                       [--filter=substring] [--report=path]
 *                                       [--state=path] [--fresh] [--commit]
 *                                       [--list]
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
    'state'  => $root . '/next/var/write-state.json',
];

foreach ($argv as $arg) {
    foreach (array_keys($options) as $name) {
        if (str_starts_with($arg, "--{$name}=")) {
            $options[$name] = substr($arg, strlen($name) + 3);
        }
    }
}

$commit = in_array('--commit', $argv, true);
$listOnly = in_array('--list', $argv, true);

/**
 * Words that mean a form changes or destroys something beyond the row it is
 * editing. Matched against the route name and the action path.
 */
const UNSAFE = [
    'delete', 'remove', 'destroy', 'drop', 'truncate', 'purge', 'reset',
    'revoke', 'cancel', 'discard', 'clear', 'wipe', 'uninstall', 'migrate',
    'import', 'restore', 'rollback', 'sync', 'send', 'mail', 'sms', 'notify',
    'payment', 'refund', 'approve', 'reject', 'confirm', 'activate',
    'deactivate', 'disable', 'enable', 'publish', 'unpublish', 'merge',
    'assign', 'generate', 'rebuild', 'backup', 'logout',
];

$isSafe = static function (string $haystack): bool {
    $haystack = strtolower($haystack);

    foreach (UNSAFE as $word) {
        if (str_contains($haystack, $word)) {
            return false;
        }
    }

    return true;
};

/** Buckets a failure by cause, so the report separates what is worth fixing. */
$classify = static function (?Throwable $e): string {
    if ($e === null) {
        return 'unclassified - worth a look';
    }

    $message = $e->getMessage();
    $class = $e::class;

    return match (true) {
        // The harness's own limits, kept separate: a sweep whose report is
        // mostly its own artefacts is worse than no sweep, because every real
        // defect in it gets read as one more piece of noise.
        str_contains($message, 'Data truncated')
            => 'HARNESS: sent a string into a numeric column',
        str_contains($message, 'FileInfoInterface')
            => 'HARNESS: an upload field it cannot satisfy',
        str_contains($message, 'Duplicate entry')
            => 'HARNESS: resubmitted a value that already exists',
        str_contains($message, 'foreign key constraint')
            => 'HARNESS: invented an id that does not exist',
        str_contains($message, 'Incorrect') && str_contains($message, 'value')
            => 'HARNESS: sent a value of the wrong shape',
        str_contains($message, 'identifier') && str_contains($message, 'is missing')
            => 'HARNESS: left an entity choice empty',
        str_contains($message, 'cannot be null')
            => 'a NOT NULL column the write never fills',
        str_contains($message, 'has no field or association named')
            => 'queries a field the entity never declared (pre-existing)',
        str_contains($message, 'Unknown column')
            => 'a column the mapping declares and the database does not',
        str_contains($message, 'no such table') || str_contains($message, "doesn't exist")
            => 'a table this dump does not have',
        str_contains($message, 'EntityManager is closed')
            => 'the entity manager was closed by an earlier failure',
        str_contains($message, 'Call to a member function') && str_contains($message, 'on null')
            => 'a null the action assumes is there',
        str_contains($message, 'must be of type') || $class === 'TypeError'
            => 'a type the action assumes it was given',
        str_contains($message, 'Undefined') || str_contains($message, 'undefined')
            => 'reads something the request does not carry',
        str_contains($message, 'does not exist') && str_contains($message, 'route')
            => 'redirects to a route that does not exist',
        default => 'unclassified - worth a look',
    };
};

/**
 * Words that mean a field holds prose. Everything else defaults to "1".
 *
 * That default is the important one. Guessing which fields are numeric from
 * their names does not work - monthlySales, openingBalance and processHead all
 * hold numbers and none of them read like it - and a string in a decimal column
 * comes back as "Data truncated for column", which looks exactly like a port
 * defect until you read it. "1" is valid in a varchar as well as in a decimal,
 * so the failure mode of guessing wrong is a dull value rather than a false
 * report.
 */
const TEXTUAL = [
    'name', 'title', 'description', 'remark', 'comment', 'address', 'note',
    'detail', 'subject', 'message', 'label', 'caption', 'content', 'body',
    'reference', 'designation', 'department', 'company', 'organization',
];

/**
 * Invents a value for a field, from its name and type.
 *
 * The aim is a value the action will accept, not a realistic one: a rejected
 * form tells you nothing about whether saving works.
 */
$valueFor = static function (string $name, string $type, ?string $rendered): ?string {
    if ($rendered !== null && $rendered !== '') {
        return $rendered;   // an id, a token, or a value the page already chose
    }

    $n = strtolower($name);

    $textual = false;

    foreach (TEXTUAL as $word) {
        if (str_contains($n, $word)) {
            $textual = true;
            break;
        }
    }

    return match (true) {
        // A file field cannot be satisfied with a scalar: the upload listeners
        // want a FileInfoInterface and throw when handed anything else.
        $type === 'file' || str_contains($n, 'upload') || str_contains($n, 'attachment')
                                                       => null,
        $type === 'email' || str_contains($n, 'email') => 'harness@example.com',
        str_contains($n, 'mobile') || str_contains($n, 'phone')
                                                       => '01' . random_int(100000000, 999999999),
        $type === 'date'                               => date('Y-m-d'),
        $type === 'datetime-local'                     => date('Y-m-d\TH:i'),
        $type === 'time'                               => date('H:i'),
        $type === 'password'                           => 'Harness-Passw0rd1',
        $type === 'url'                                => 'https://example.com',
        str_contains($n, 'slug')                       => 'harness-' . random_int(1000, 9999),
        $textual                                       => 'Harness ' . random_int(1000, 9999),
        default                                        => '1',
    };
};

/**
 * Reads every form out of a rendered page.
 *
 * @return list<array{action: string, method: string, fields: array<string, mixed>, count: int}>
 */
$formsIn = static function (string $html, string $pagePath) use ($valueFor): array {
    if (trim($html) === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $forms = [];

    /** @var DOMElement $form */
    foreach ($dom->getElementsByTagName('form') as $form) {
        $method = strtoupper($form->getAttribute('method') ?: 'GET');
        $action = $form->getAttribute('action') ?: $pagePath;

        // Only same-origin paths; an absolute URL leaves the application.
        if (preg_match('#^[a-z]+://#i', $action)) {
            continue;
        }

        $action = '/' . ltrim(parse_url($action, PHP_URL_PATH) ?: $pagePath, '/');
        $fields = [];
        $count = 0;

        foreach (['input', 'select', 'textarea'] as $tag) {
            /** @var DOMElement $el */
            foreach ($form->getElementsByTagName($tag) as $el) {
                $name = $el->getAttribute('name');

                if ($name === '') {
                    continue;
                }

                $type = strtolower($el->getAttribute('type') ?: ($tag === 'input' ? 'text' : $tag));

                if ($type === 'submit' || $type === 'button' || $type === 'reset' || $type === 'image') {
                    continue;
                }

                // An unchecked box is simply absent from a real submission, and
                // checking one can turn a feature on rather than exercise a save.
                if (($type === 'checkbox' || $type === 'radio') && !$el->hasAttribute('checked')) {
                    continue;
                }

                if ($tag === 'select') {
                    $chosen = null;

                    /** @var DOMElement $option */
                    foreach ($el->getElementsByTagName('option') as $option) {
                        $v = $option->getAttribute('value');

                        if ($v !== '') {
                            $chosen = $v;
                            break;
                        }
                    }

                    if ($chosen === null) {
                        continue;
                    }

                    $value = $chosen;
                } elseif ($tag === 'textarea') {
                    $value = trim($el->textContent) !== '' ? $el->textContent : 'harness';
                } else {
                    $value = $valueFor($name, $type, $el->getAttribute('value'));
                }

                if ($value === null) {
                    continue;
                }

                ++$count;

                // Expand PHP's name[a][b] notation into the nested array
                // Request::create expects.
                if (preg_match_all('/\[([^\]]*)\]/', $name, $m)) {
                    $base = substr($name, 0, strpos($name, '['));
                    $ref = &$fields[$base];

                    foreach ($m[1] as $key) {
                        if ($key === '') {
                            $ref = is_array($ref) ? $ref : [];
                            $ref[] = $value;
                            continue 2;
                        }

                        $ref = is_array($ref) ? $ref : [];
                        $ref = &$ref[$key];
                    }

                    $ref = $value;
                    unset($ref);
                } else {
                    $fields[$name] = $value;
                }
            }
        }

        $forms[] = ['action' => $action, 'method' => $method, 'fields' => $fields, 'count' => $count];
    }

    return $forms;
};

// ---------------------------------------------------------------------------

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
$user = $registry->getManager()->getRepository(User::class)
    ->findOneBy(['usernameCanonical' => $options['user']]);

if ($user === null) {
    fwrite(STDERR, sprintf("no such user: %s\n", $options['user']));
    exit(2);
}

$session = new Session(new MockArraySessionStorage());
$session->set('_security_main', serialize(new UsernamePasswordToken($user, 'main', $user->getRoles())));

$send = static function (string $path, string $method, array $parameters, ?string $referer = null) use ($kernel, $session): Request {
    $request = Request::create($path, $method, $parameters);
    $request->setSession($session);
    $request->cookies->set($session->getName(), 'write-test');

    // Actions redirect back to where the form was, and several read the header
    // rather than generating a route. Without it they call redirect(null),
    // which is a TypeError that says nothing about the port.
    if ($referer !== null) {
        $request->headers->set('referer', $referer);
    }

    return $request;
};

// Edit forms live at /thing/{id}/edit, so skipping placeholders skipped most of
// the writes worth exercising. RouteValues fills them from real rows.
$withParameters = !in_array('--no-parameters', $argv, true);
$values = new RouteValues($registry->getManager(), $user->getGlobalOption()?->getId());

$routes = $container->get('router')->getRouteCollection();

$state = ['seen' => [], 'dead' => [], 'failures' => [], 'pending' => null, 'pendingPage' => null];

if (!in_array('--fresh', $argv, true) && is_file($options['state'])) {
    $state = json_decode((string) file_get_contents($options['state']), true) ?: $state;

    if (($state['pending'] ?? null) !== null) {
        $state['seen'][$state['pending']] = 'exit';
        $state['pending'] = null;
    }

    // A page recorded as pending never came back: rendering it killed the
    // process before any form on it could be reached. Without this the next
    // run renders it again and dies in the same place, forever.
    if (($state['pendingPage'] ?? null) !== null) {
        $state['dead'][$state['pendingPage']] = true;
        $state['pendingPage'] = null;
    }

    printf("resuming: %d form(s) submitted, %d page(s) that kill the process\n",
        count($state['seen']), count($state['dead'] ?? []));
}

$counts = ['saved' => 0, 'rejected' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'exit' => 0, 'skipped' => 0];
$failures = $state['failures'];
$current = null;
$submitted = 0;
$pagesWithForms = 0;

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

    if (($state['pendingPage'] ?? null) !== null) {
        $state['dead'][$state['pendingPage']] = true;
        $state['pendingPage'] = null;
    }

    $saveState();
});

foreach ($routes as $name => $route) {
    if ($options['limit'] > 0 && $submitted >= (int) $options['limit']) {
        break;
    }

    $path = $route->getPath();
    $methods = $route->getMethods();

    if (($methods !== [] && !in_array('GET', $methods, true))
        || str_starts_with($path, '/_')
        || str_starts_with($path, '/android-api')
        || str_starts_with($path, '/flutter-api')
        || !$isSafe($name . ' ' . $path)
        || isset($state['dead'][$name])
        || ($options['filter'] !== '' && !str_contains($name, (string) $options['filter']))
    ) {
        ++$counts['skipped'];
        continue;
    }

    if (str_contains($path, '{')) {
        $path = $withParameters ? $values->fill($name, $route) : null;

        if ($path === null) {
            ++$counts['skipped'];
            continue;
        }
    }

    // Render the page the form lives on. Failures here are smoke-test's
    // business, not this harness's - but a render that calls exit; has to be
    // recorded before it happens, or every later run dies in the same place.
    $thrown = null;
    $state['pendingPage'] = $name;
    $saveState();

    try {
        $response = $kernel->handle($send($path, 'GET', []), HttpKernelInterface::MAIN_REQUEST, false);
    } catch (Throwable) {
        $state['pendingPage'] = null;
        ++$counts['skipped'];
        continue;
    }

    $state['pendingPage'] = null;

    if ($response->getStatusCode() !== 200) {
        ++$counts['skipped'];
        continue;
    }

    $forms = $formsIn((string) $response->getContent(), $path);
    // A form with nothing in it carries no write to exercise - the search boxes
    // and the logout stubs the layout renders on every page.
    $writable = array_values(array_filter(
        $forms,
        static fn (array $f): bool => $f['count'] > 0
            && in_array($f['method'], ['POST', 'PUT', 'PATCH'], true)
    ));

    if ($writable === []) {
        continue;
    }

    ++$pagesWithForms;

    foreach ($writable as $i => $form) {
        $key = $name . '#' . $i . ' -> ' . $form['action'];

        if (isset($state['seen'][$key])) {
            $bucket = $state['seen'][$key];
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
            continue;
        }

        if (!$isSafe($form['action'])) {
            ++$counts['skipped'];
            continue;
        }

        if ($listOnly) {
            printf("  %-58s %d field(s)\n", $key, $form['count']);
            ++$submitted;
            continue;
        }

        $connection = $registry->getConnection();
        $connection->setNestTransactionsWithSavepoints(true);

        if (!$commit) {
            $connection->beginTransaction();
        }

        $thrown = null;
        $current = $key;
        $state['pending'] = $key;
        $saveState();

        try {
            $response = $kernel->handle(
                $send($form['action'], $form['method'], $form['fields'], 'http://localhost' . $path),
                HttpKernelInterface::MAIN_REQUEST,
                false
            );
            $status = $response->getStatusCode();
        } catch (HttpExceptionInterface $e) {
            $thrown = null;
            $status = $e->getStatusCode();
        } catch (Throwable $e) {
            $thrown ??= $e;
            $status = 500;
        }

        $current = null;
        $state['pending'] = null;
        ++$submitted;

        // A redirect is what a controller does when a save succeeded; a 200 is
        // the form rendered again, which means it was rejected.
        $bucket = match (true) {
            $status >= 500                 => '5xx',
            $status >= 400                 => '4xx',
            $status >= 300                 => '3xx',
            default                        => 'rejected',
        };

        if ($bucket === '3xx') {
            $bucket = 'saved';
        }

        $state['seen'][$key] = $bucket;
        $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;

        if ($bucket === '5xx') {
            $label = $thrown === null
                ? 'unclassified - worth a look'
                : $classify($thrown);
            $detail = $thrown === null
                ? 'HTTP 500 with no exception captured'
                : $thrown::class . ': ' . $thrown->getMessage();
            $failures[$label][] = $key . "\n      " . substr($detail, 0, 200);
        }

        if (!$commit) {
            try {
                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }
            } catch (Throwable) {
                // The connection went with the failure; the next request opens
                // a new one and MySQL has already discarded the transaction.
            }
        }

        // Doctrine closes the manager on a failed flush, and every later
        // request would fail the same way. Reset it and carry on.
        if (!$registry->getManager()->isOpen()) {
            $registry->resetManager();
        }

        $saveState();
    }
}

$saveState();

printf("\nsubmitted %d form(s) across %d page(s) this run%s\n",
    $submitted, $pagesWithForms, $commit ? ' (committing)' : ' (rolled back)');

foreach ($counts as $label => $n) {
    printf("  %-9s %d\n", $label, $n);
}

if ($options['report'] === null) {
    return;
}

$lines = [
    '# Write test',
    '',
    'Generated by `migration/bin/write-test.php`. Each form the application',
    'renders is submitted back through the kernel, inside a transaction that is',
    'rolled back. Destructive actions are skipped by name.',
    '',
    sprintf('- forms submitted: **%d**', array_sum(array_intersect_key($counts, array_flip(['saved', 'rejected', '3xx', '4xx', '5xx', 'exit'])))),
    sprintf('- saved (redirected): **%d**  rejected (re-rendered): **%d**  4xx: **%d**  5xx: **%d**  exit: **%d**',
        $counts['saved'], $counts['rejected'], $counts['4xx'], $counts['5xx'], $counts['exit']),
    '',
    '## By cause',
    '',
    '| forms | cause |',
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
