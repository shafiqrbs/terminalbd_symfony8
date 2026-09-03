<?php
/**
 * Converts SF2 controllers to Symfony 8: AbstractController plus constructor
 * injection in place of the service locator.
 *
 * Symfony 8's AbstractController has neither getDoctrine() nor get(), so every
 * one of the ~7,275 call sites is a runtime fatal. None of the 336 controllers
 * declares a constructor, so one can be added safely.
 *
 * Services with no clean equivalent are NOT guessed at - they keep their
 * original call and gain a TODO(migration) marker, so they fail loudly and stay
 * greppable rather than silently doing the wrong thing.
 *
 * usage: php migration/bin/port-controllers.php <dir> [--dry-run]
 */
$dir    = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv, true);
if (!$dir || !is_dir($dir)) { fwrite(STDERR, "usage: port-controllers.php <dir> [--dry-run]\n"); exit(1); }

/** service id => [property, type, FQCN to import] */
$INJECT = [
    'session'                  => ['requestStack',    'RequestStack',              'Symfony\Component\HttpFoundation\RequestStack'],
    'knp_paginator'            => ['paginator',       'PaginatorInterface',        'Knp\Component\Pager\PaginatorInterface'],
    'settong.toolManageRepo'   => ['toolManager',     'ToolManager',               'App\Tool\Service\ToolManager'],
    'setting.menuSettingRepo'  => ['menuManager',     'MenuManager',               'App\Appearance\Service\MenuManager'],
    'twig'                     => ['twig',            'Environment',               'Twig\Environment'],
    'event_dispatcher'         => ['eventDispatcher', 'EventDispatcherInterface',  'Symfony\Contracts\EventDispatcher\EventDispatcherInterface'],
    'doctrine.orm.entity_manager' => ['em',           'EntityManagerInterface',    'Doctrine\ORM\EntityManagerInterface'],
];

const EM = ['em', 'EntityManagerInterface', 'Doctrine\ORM\EntityManagerInterface'];

$stats = ['files' => 0, 'base' => 0, 'doctrine' => 0, 'services' => 0, 'marked' => 0];
$unmapped = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    if (!str_contains($path, '/Controller/')) continue;

    $src = (string) file_get_contents($path);
    $out = $src;

    // ---- 1. base class -----------------------------------------------------
    // Symfony\Bundle\FrameworkBundle\Controller\Controller was removed in SF5.
    //
    // "extends Controller" does NOT always mean that class: two controllers
    // alias a FOSUserBundle controller to the same name
    // ("use FOS\UserBundle\Controller\GroupController as Controller"). Rewriting
    // those would silently change which class they extend, so the FrameworkBundle
    // import has to actually be present.
    $hasFrameworkBase = (bool) preg_match(
        '/^use\s+Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\Controller\s*;/m',
        $out
    );
    if (!$hasFrameworkBase && preg_match('/\bextends\s+Controller\b/', $out)) {
        $out = preg_replace(
            '/^(class\s+\w+\s+extends\s+Controller\b)/m',
            '// TODO(migration): extends an aliased FOSUserBundle controller that no'
            . " longer exists.\n" . '// Reimplement the inherited behaviour on AbstractController.' . "\n" . '$1',
            $out, 1
        );
        $stats['marked']++;
    }
    if ($hasFrameworkBase && preg_match('/\bextends\s+Controller\b/', $out)) {
        $out = preg_replace(
            '/use\s+Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\Controller\s*;/',
            'use Symfony\\Bundle\\FrameworkBundle\\Controller\\AbstractController;',
            $out
        );
        $out = preg_replace('/(\bclass\s+\w+\s+extends\s+)Controller\b/', '$1AbstractController', $out);
        $stats['base']++;
    }

    $needs = [];

    // ---- 2. doctrine -------------------------------------------------------
    if (str_contains($out, '$this->getDoctrine()')) {
        $n = 0;
        $out = str_replace('$this->getDoctrine()->getManager()', '$this->em', $out, $n);      $stats['doctrine'] += $n;
        $out = str_replace('$this->getDoctrine()->getRepository(', '$this->em->getRepository(', $out, $n); $stats['doctrine'] += $n;
        $needs[EM[0]] = EM;

        // anything still calling getDoctrine() wants the registry itself
        if (str_contains($out, '$this->getDoctrine()')) {
            $out = preg_replace(
                '/^([ \t]*)(.*\$this->getDoctrine\(\).*)$/m',
                '$1// TODO(migration): AbstractController::getDoctrine() was removed; inject ManagerRegistry.' . "\n" . '$1$2',
                $out
            );
            $stats['marked']++;
        }
    }

    // ---- 3. service locator ------------------------------------------------
    // isGranted() and getUser() are provided by AbstractController, so these
    // need no injection at all.
    $out = preg_replace(
        "/\\\$this->get\('security\.(authorization_checker|context)'\)->isGranted\(/",
        '$this->isGranted(',
        $out, -1, $n
    );
    $stats['services'] += $n;

    $out = str_replace(
        "\$this->get('security.context')->getToken()->getUser()",
        '$this->getUser()',
        $out, $n
    );
    $stats['services'] += $n;

    // Symfony 3 removed the "request" service. RequestStack is the
    // behaviour-preserving replacement; an idiomatic port would instead take
    // Request as an action argument, but that changes every action signature.
    foreach ([
        "\$this->get('request')->getSession()" => '$this->requestStack->getSession()',
        "\$this->get('request')->query"        => '$this->requestStack->getCurrentRequest()->query',
        "\$this->get('request')->request"      => '$this->requestStack->getCurrentRequest()->request',
        "\$this->get('request')->"             => '$this->requestStack->getCurrentRequest()->',
    ] as $needle => $replacement) {
        if (!str_contains($out, $needle)) continue;
        $out = str_replace($needle, $replacement, $out, $n);
        $stats['services'] += $n;
        $needs['requestStack'] = ['requestStack', 'RequestStack', 'Symfony\Component\HttpFoundation\RequestStack'];
    }

    if (str_contains($out, "\$this->get('security.context')->setToken(")) {
        $out = str_replace(
            "\$this->get('security.context')->setToken(",
            '$this->tokenStorage->setToken(',
            $out, $n
        );
        $stats['services'] += $n;
        $needs['tokenStorage'] = ['tokenStorage', 'TokenStorageInterface', 'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'];
    }

    foreach ($INJECT as $id => [$prop, $type, $fqcn]) {
        $needle = "\$this->get('$id')";
        if (!str_contains($out, $needle)) continue;
        $replacement = $id === 'session' ? '$this->requestStack->getSession()' : "\$this->$prop";
        $out = str_replace($needle, $replacement, $out, $n);
        $stats['services'] += $n;
        $needs[$prop] = [$prop, $type, $fqcn];
    }

    // whatever is left has no safe mechanical equivalent
    if (preg_match_all("/\\\$this->get\('([a-zA-Z0-9_.]+)'\)/", $out, $mm)) {
        foreach ($mm[1] as $id) $unmapped[$id] = ($unmapped[$id] ?? 0) + 1;
        $out = preg_replace(
            "/^([ \t]*)(?!.*TODO\(migration\))(.*\\\$this->get\('[a-zA-Z0-9_.]+'\).*)$/m",
            '$1// TODO(migration): AbstractController::get() was removed; inject this service.' . "\n" . '$1$2',
            $out
        );
        $stats['marked']++;
    }

    // ---- 4. constructor ----------------------------------------------------
    if ($needs) {
        foreach ($needs as [$prop, $type, $fqcn]) {
            if (!preg_match('/^use\s+' . preg_quote($fqcn, '/') . '\s*;/m', $out)) {
                $out = preg_replace('/^(namespace\s+[^;]+;\s*\R)/m', '$1use ' . $fqcn . ";\n", $out, 1);
            }
        }
        $params = [];
        foreach ($needs as [$prop, $type, $fqcn]) {
            $params[] = "        private readonly $type \$$prop,";
        }
        $ctor = "\n    public function __construct(\n" . implode("\n", $params) . "\n    ) {\n    }\n";

        // insert right after the class opening brace
        $out = preg_replace(
            '/(\bclass\s+\w+\s+extends\s+AbstractController[^{]*\{)/',
            '$1' . str_replace('$', '\\$', $ctor),
            $out,
            1
        );
    }

    if ($out === $src) continue;
    $stats['files']++;
    if (!$dryRun) file_put_contents($path, $out);
}

printf("%s %d controllers\n", $dryRun ? 'would rewrite' : 'rewrote', $stats['files']);
printf("  base class -> AbstractController : %d\n", $stats['base']);
printf("  getDoctrine() call sites rewritten: %d\n", $stats['doctrine']);
printf("  service locator calls rewritten   : %d\n", $stats['services']);
printf("  files with TODO markers           : %d\n", $stats['marked']);
if ($unmapped) {
    arsort($unmapped);
    printf("\nservices left for manual injection:\n");
    foreach (array_slice($unmapped, 0, 12, true) as $id => $n) printf("  %-40s %d\n", $id, $n);
}
