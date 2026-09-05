<?php

declare(strict_types=1);

/**
 * AbstractController::get() was removed in Symfony 6.
 *
 * Every remaining $this->get('id') / $this->container->get('id') is rewritten
 * to a constructor-injected dependency, or - where Symfony 8 has a first-class
 * equivalent - to the controller helper:
 *
 *   security.context / security.token_storage ->getToken()->getUser()
 *                                        -> $this->getUser()
 *   security.authorization_checker->isGranted -> $this->isGranted()
 *   router->generate()                        -> $this->generateUrl()
 *
 * Application service ids are resolved through config/services_ported.yaml,
 * so the class injected is the one the legacy definition named.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-service-locator.php [--dry-run]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

/** Framework ids -> [FQCN, property name]. */
const FRAMEWORK = [
    'doctrine' => ['Doctrine\Persistence\ManagerRegistry', 'doctrine'],
    // security.context went in Symfony 3; Security carries the same three
    // methods the call sites use (getToken, getUser, isGranted).
    'security.context' => ['Symfony\Bundle\SecurityBundle\Security', 'security'],
    'doctrine.dbal.default_connection' => ['Doctrine\DBAL\Connection', 'connection'],
    'event_dispatcher' => ['Symfony\Contracts\EventDispatcher\EventDispatcherInterface', 'eventDispatcher'],
    'security.token_storage' => ['Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface', 'tokenStorage'],
    'security.authorization_checker' => ['Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface', 'authorizationChecker'],
    'security.csrf.token_manager' => ['Symfony\Component\Security\Csrf\CsrfTokenManagerInterface', 'csrfTokenManager'],
    'form.csrf_provider' => ['Symfony\Component\Security\Csrf\CsrfTokenManagerInterface', 'csrfTokenManager'],
    'request_stack' => ['Symfony\Component\HttpFoundation\RequestStack', 'requestStack'],
    'router' => ['Symfony\Component\Routing\RouterInterface', 'router'],
    'translator' => ['Symfony\Contracts\Translation\TranslatorInterface', 'translator'],
    'validator' => ['Symfony\Component\Validator\Validator\ValidatorInterface', 'validator'],
    'form.factory' => ['Symfony\Component\Form\FormFactoryInterface', 'formFactory'],
    'kernel' => ['Symfony\Component\HttpKernel\KernelInterface', 'kernel'],
    'twig' => ['Twig\Environment', 'twig'],
    'stof_doctrine_extensions.uploadable.manager' => ['Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager', 'uploadableManager'],
];

// ---- id -> class, from the ported definitions -----------------------------

$ported = Yaml::parseFile($root . '/config/services_ported.yaml');
$idToClass = [];

foreach ($ported['services'] ?? [] as $id => $definition) {
    if (isset($definition['class']) && str_starts_with((string) $definition['class'], 'App\\')) {
        $idToClass[$id] = $definition['class'];
    } elseif (isset($definition['alias']) && str_starts_with((string) $definition['alias'], 'App\\')) {
        $idToClass[$id] = $definition['alias'];
    }
}

$propertyFor = static function (string $class): string {
    $short = substr($class, strrpos($class, '\\') + 1);
    $short = preg_replace('/Interface$/', '', $short);

    return lcfirst($short);
};

$stats = ['helper rewrites' => 0, 'injected' => 0, 'files' => 0];
$unknown = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    // Files whose calls are already converted still carry the marker.
    if (!preg_match('/\$this->(container->)?get(Doctrine)?\(/', $code)
        && !str_contains($code, 'TODO(migration): AbstractController::')) {
        continue;
    }

    $original = $code;
    $isController = str_ends_with($path, 'Controller.php');

    // Doctrine builds repositories itself, with (EntityManager, ClassMetadata):
    // giving one a constructor breaks every query through it. The legacy code
    // that calls $this->get() inside a repository was already fatal - an
    // EntityRepository has no get() - so it is reported, not converted.
    if (preg_match('/class\s+\w+\s+extends\s+\w*EntityRepository\b/', $code)) {
        preg_match_all('/\$this->(?:container->)?get\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $code, $inRepository);

        foreach (array_unique($inRepository[1]) as $id) {
            $unknown['(in a Doctrine repository) ' . $id][] = str_replace($root . '/', '', $path);
        }

        continue;
    }

    // ---- 1. things Symfony 8 exposes on AbstractController ----------------
    if ($isController) {
        $before = $code;

        $code = preg_replace(
            '/\$this->(?:container->)?get\(\s*[\'"](?:security\.context|security\.token_storage)[\'"]\s*\)->getToken\(\)->getUser\(\)/',
            '$this->getUser()',
            $code,
        );
        $code = preg_replace(
            '/\$this->(?:container->)?get\(\s*[\'"](?:security\.context|security\.authorization_checker)[\'"]\s*\)->isGranted\(/',
            '$this->isGranted(',
            $code,
        );
        $code = preg_replace(
            '/\$this->(?:container->)?get\(\s*[\'"]router[\'"]\s*\)->generate\(/',
            '$this->generateUrl(',
            $code,
        );

        if ($code !== $before) {
            $stats['helper rewrites'] += substr_count($before, "get('security.context')")
                + substr_count($before, "get('security.token_storage')")
                + substr_count($before, "get('security.authorization_checker')")
                + substr_count($before, "get('router')");
        }
    }

    // AbstractController::getDoctrine() went in Symfony 6 as well. Treat it as
    // a call for the `doctrine` service, so it is injected like the rest.
    $code = preg_replace(
        '/\$this->getDoctrine\(\)/',
        "\$this->get('doctrine')",
        $code,
        -1,
        $n,
    );
    $stats['helper rewrites'] += $n;

    // ---- 2. inject whatever is left --------------------------------------
    preg_match_all('/\$this->(?:container->)?get\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $code, $matches);

    $needed = [];

    foreach (array_unique($matches[1]) as $id) {
        if (isset(FRAMEWORK[$id])) {
            [$class, $property] = FRAMEWORK[$id];
        } elseif (isset($idToClass[$id])) {
            $class = $idToClass[$id];
            $property = $propertyFor($class);
        } else {
            $unknown[$id][] = str_replace($root . '/', '', $path);
            continue;
        }

        $needed[$id] = [$class, $property];
    }

    foreach ($needed as $id => [$class, $property]) {
        // Reuse a dependency the class already holds.
        if (preg_match('/(?:private|protected|public)\s+(?:readonly\s+)?\\\\?' . preg_quote($class, '/') . '\s+\$(\w+)/', $code, $pm)) {
            $property = $pm[1];
        } elseif (preg_match('/use\s+' . preg_quote($class, '/') . ';/', $code)
            && preg_match('/(?:private|protected|public)\s+(?:readonly\s+)?' . preg_quote(substr($class, strrpos($class, '\\') + 1), '/') . '\s+\$(\w+)/', $code, $pm)) {
            $property = $pm[1];
        } else {
            // Add the constructor parameter.
            $short = substr($class, strrpos($class, '\\') + 1);

            // An untyped legacy property of the same name would collide with
            // the promoted one; the constructor parameter replaces it.
            $code = preg_replace(
                '/\n\s*(?:private|protected|public)\s+\$' . preg_quote($property, '/') . '\s*;\n/',
                "\n",
                $code,
            );

            if (!preg_match('/^use\s+' . preg_quote($class, '/') . ';/m', $code)) {
                $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse {$class};\n", $code, 1);
            }

            $param = "        private readonly {$short} \${$property},\n";

            if (preg_match('/public function __construct\(\s*\n(.*?)\n(\s*)\)\s*\{/s', $code, $cm, PREG_OFFSET_CAPTURE)) {
                $insertAt = $cm[1][1] + strlen($cm[1][0]);
                $code = substr($code, 0, $insertAt) . "\n" . rtrim($param, "\n") . substr($code, $insertAt);
            } elseif (preg_match('/public function __construct\(([^)]*)\)\s*\{\s*\}/s', $code, $cm, PREG_OFFSET_CAPTURE)) {
                $existing = trim($cm[1][0]);
                $replacement = "public function __construct(\n"
                    . ($existing === '' ? '' : "        {$existing},\n")
                    . $param
                    . "    ) {\n    }";
                $code = substr($code, 0, $cm[0][1]) . $replacement . substr($code, $cm[0][1] + strlen($cm[0][0]));
            } else {
                // No constructor at all: add one after the class declaration.
                $code = preg_replace(
                    '/(class\s+\w+[^{]*\{\n)/',
                    "$1    public function __construct(\n" . $param . "    ) {\n    }\n\n",
                    $code,
                    1,
                );
            }

            // A Command subclass must run Command::__construct(), which is
            // what installs the name from #[AsCommand].
            if (preg_match('/class\s+\w+\s+extends\s+Command\b/', $code)
                && !str_contains($code, 'parent::__construct()')) {
                $code = preg_replace(
                    '/(public function __construct\((?:[^{]*?)\)\s*\{)(\s*\})/s',
                    "$1\n        parent::__construct();\n    }",
                    $code,
                    1,
                );
            }

            ++$stats['injected'];
        }

        $code = preg_replace(
            '/\$this->(?:container->)?get\(\s*[\'"]' . preg_quote($id, '/') . '[\'"]\s*\)/',
            '$this->' . $property,
            $code,
        );
    }

    // port-controllers.php leaves a marker above each $this->get() it could
    // not convert; the ones converted here no longer need it.
    $lines = explode("\n", $code);
    $kept = [];

    for ($i = 0, $count = count($lines); $i < $count; ++$i) {
        if (preg_match('/^\s*\/\/ TODO\(migration\): AbstractController::(get|getDoctrine)\(\) was removed/', $lines[$i])
            && isset($lines[$i + 1])
            && !preg_match('/(?:this|container)->get(?:Doctrine)?\(/', $lines[$i + 1])) {
            continue;
        }

        $kept[] = $lines[$i];
    }

    $code = implode("\n", $kept);

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

foreach ($stats as $label => $count) {
    printf("%-18s %d\n", $label, $count);
}

if ($unknown !== []) {
    echo "\nids with no definition in either tree (left alone):\n";

    foreach ($unknown as $id => $files) {
        printf("    %-52s %d file(s)\n", $id, count(array_unique($files)));
    }
}
