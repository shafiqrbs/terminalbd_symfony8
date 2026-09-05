<?php

declare(strict_types=1);

/**
 * Ports the bundles' Resources/config/services.yml into the new application.
 *
 * Bundle DI extensions are gone with the bundles, so none of these definitions
 * came across with the classes. That mattered: the SMS/e-mail listeners are
 * tagged services, and an untagged autowired class is simply never called - the
 * dispatches in the controllers were landing nowhere.
 *
 * Legacy service ids are preserved. The classes are remapped to App\<Module>\,
 * ids that belonged to removed bundles are remapped to their replacement, and
 * a definition whose class no longer exists is dropped and reported.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-services.php [--dry-run]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__, 2);
$next = $root . '/next';
$dryRun = in_array('--dry-run', $argv, true);

// ---- legacy namespace -> App\<Module> -------------------------------------

$nsMap = [];

foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: [],
) as $file) {
    $dir = basename(dirname($file));

    if (!str_ends_with($dir, 'Bundle')) {
        continue;
    }

    $module = substr($dir, 0, -strlen('Bundle'));
    $source = (string) file_get_contents($file);

    if (preg_match('/^namespace\s+([A-Za-z\\\\]+);/m', $source, $m)) {
        $nsMap[$m[1]] = 'App\\' . $module;
    }
}

uksort($nsMap, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

/** Services that lived in bundles this migration removed. */
const CLASS_MAP = [
    // twig/extensions is abandoned and has no Twig 3 release; the one filter
    // the templates use is reproduced in App\Tool\Twig\TextExtension.
    'Twig_Extensions_Extension_Text' => 'App\Tool\Twig\TextExtension',
];

const ID_MAP = [
    'phpexcel' => 'App\Tool\Service\SpreadsheetFactory',
    'fos_user.user_manager' => 'App\User\Manager\UserManager',
    'fos_user.group_manager' => 'App\User\Manager\GroupManager',
    'fos_user.util.token_generator' => 'App\User\Util\TokenGenerator',
    'fos_user.mailer' => 'App\User\Mailer\ResettingMailer',
];

/** Ids the new application provides under a different name. */
const ARG_MAP = [
    '@phpexcel' => '@App\Tool\Service\SpreadsheetFactory',
    '@fos_user.user_manager' => '@App\User\Manager\UserManager',
    '@fos_user.group_manager' => '@App\User\Manager\GroupManager',
    '@mailer' => '@mailer',
    '@templating' => '@twig',
    '@security.context' => '@security.token_storage',
];

$rewriteClass = static function (string $class) use ($nsMap): string {
    foreach ($nsMap as $legacy => $new) {
        if (str_starts_with($class, $legacy . '\\')) {
            return $new . substr($class, strlen($legacy));
        }
    }

    return $class;
};

$classExists = static function (string $class) use ($next): bool {
    return file_exists($next . '/src/' . str_replace('\\', '/', substr($class, strlen('App\\'))) . '.php')
        || !str_starts_with($class, 'App\\');
};

// ---- read every bundle's services.yml -------------------------------------

$services = [];
$parameters = [];
$dropped = [];

foreach (glob("$root/src/*/*/*/Resources/config/services.yml") ?: [] as $path) {
    $files[] = $path;
}

foreach (glob("$root/src/*/*/Resources/config/services.yml") ?: [] as $path) {
    $files[] = $path;
}

// app/config/config.yml carries three service definitions of its own.
$files[] = "$root/app/config/config.yml";

foreach ($files ?? [] as $path) {
    $raw = (string) file_get_contents($path);

    // Symfony 2 wrote service references unquoted (@foo, %param%); YAML
    // reserves '@' as an indicator, so quote them before parsing.
    $raw = preg_replace('/(?<=[\s\[,])@([\w.\\\\]+)/', '"@$1"', $raw);
    $raw = preg_replace('/(?<=[\s\[,])(%[\w.\-]+%)(?=[\s,\]])/', '"$1"', $raw);

    try {
        $parsed = Yaml::parse($raw);
    } catch (Throwable $e) {
        $dropped[] = str_replace($root . '/', '', $path) . ': unparsable (' . $e->getMessage() . ')';
        continue;
    }

    foreach ($parsed['parameters'] ?? [] as $name => $value) {
        $parameters[$name] = is_string($value) ? $rewriteClass($value) : $value;
    }

    foreach ($parsed['services'] ?? [] as $id => $definition) {
        if (!is_array($definition) || !isset($definition['class'])) {
            continue;
        }

        $class = CLASS_MAP[$definition['class']] ?? $rewriteClass($definition['class']);

        if (!$classExists($class)) {
            $dropped[] = $id . ' (' . $definition['class'] . ' no longer exists)';
            continue;
        }

        $definition['class'] = $class;

        $mapArgs = static function (array $args) use (&$mapArgs, $rewriteClass): array {
            foreach ($args as $key => $value) {
                if (is_array($value)) {
                    $args[$key] = $mapArgs($value);
                } elseif (is_string($value)) {
                    $value = trim($value);
                    $args[$key] = ARG_MAP[$value] ?? $value;
                }
            }

            return $args;
        };

        foreach (['arguments', 'calls'] as $key) {
            if (isset($definition[$key]) && is_array($definition[$key])) {
                $definition[$key] = $mapArgs($definition[$key]);
            }
        }

        // setContainer() went away with ContainerAwareTrait (Symfony 7); the
        // importers take a ManagerRegistry through an autowired setter now.
        if (isset($definition['calls'])) {
            $definition['calls'] = array_values(array_filter(
                $definition['calls'],
                static fn ($call): bool => ($call[0] ?? '') !== 'setContainer',
            ));

            if ($definition['calls'] === []) {
                unset($definition['calls']);
            }
        }

        // Symfony 2 listed every constructor argument positionally, including
        // the ones that are now autowired, and several of those services are
        // gone (fos_user.*, security.encoder_factory). Keep only the scalars
        // the constructor actually needs and let autowiring do the rest.
        $reflection = class_exists($class) ? new ReflectionClass($class) : null;
        $ctor = $reflection?->getConstructor();
        $scalars = [];

        if ($ctor !== null && isset($definition['arguments'])) {
            $legacyArgs = array_values($definition['arguments']);

            foreach ($ctor->getParameters() as $i => $param) {
                $type = $param->getType();
                $isScalar = $type === null
                    || ($type instanceof ReflectionNamedType && $type->isBuiltin());

                if (!$isScalar || !isset($legacyArgs[$i]) || !is_string($legacyArgs[$i])) {
                    continue;
                }

                $value = trim($legacyArgs[$i]);

                if (str_starts_with($value, '@')) {
                    continue;
                }

                $scalars['$' . $param->getName()] = $value;
            }
        }

        if ($scalars !== []) {
            $definition['arguments'] = $scalars;
        } else {
            unset($definition['arguments']);
        }

        // An argument or setter that still points at a service this migration
        // removed cannot be built; report it rather than emitting a definition
        // that fails container compilation.
        $unknown = null;

        array_walk_recursive($definition, static function ($value) use (&$unknown): void {
            if (is_string($value) && preg_match('/^@(fos_user\.|security\.encoder_factory|sg_datatables|knp_snappy)/', $value)) {
                $unknown = $value;
            }
        });

        if ($unknown !== null) {
            $dropped[] = $id . ' (references removed service ' . $unknown . ')';
            continue;
        }

        $definition['autowire'] = true;
        $definition['autoconfigure'] = false;
        // Legacy ids are fetched by name from controllers, so keep them public.
        $definition['public'] = true;

        $services[$id] = $definition;
    }
}

ksort($services);

// The SMS gateway was built by eightpoints/guzzle-bundle, which has no
// Symfony 8 release; the client is defined here with the same base URI and
// headers app/config/config.yml gave it.
$services['guzzle.client.api_crm'] = [
    'class' => 'GuzzleHttp\Client',
    'public' => true,
    'arguments' => [[
        'base_uri' => 'http://messaging.icombd.com:9000',
        'headers' => ['Accept' => 'application/json'],
    ]],
];

$services['GuzzleHttp\\Client'] = ['alias' => 'guzzle.client.api_crm', 'public' => true];

foreach (array_keys(ID_MAP) as $legacyId) {
    $services[$legacyId] = ['alias' => ID_MAP[$legacyId], 'public' => true];
}

// The App\ resource in services_legacy.yaml also auto-registers these classes,
// and that autowired definition breaks on the scalar constructor arguments the
// definition above supplies (SmsGateWay's %sms_username%, say). Point the class
// id at the configured service so there is exactly one instance and autowiring
// by type finds it.
$byClass = [];

foreach ($services as $id => $definition) {
    if (isset($definition['class']) && str_starts_with((string) $definition['class'], 'App\\')) {
        $byClass[$definition['class']][] = $id;
    }
}

foreach ($byClass as $class => $ids) {
    if (isset($services[$class])) {
        continue;
    }

    // Registered under several ids (the same listener on three events): keep
    // the class id as its own definition rather than aliasing one arbitrarily.
    $services[$class] = count($ids) === 1
        ? ['alias' => $ids[0], 'public' => true]
        : ['class' => $class, 'autowire' => true, 'autoconfigure' => false, 'public' => true]
            + array_intersect_key($services[$ids[0]], ['arguments' => true, 'calls' => true]);
}

ksort($services);

$out = [
    '# GENERATED by migration/bin/port-services.php - do not edit by hand.',
    '#',
    '# The service definitions from the bundles\' Resources/config/services.yml.',
    '# Bundle extensions no longer load them, and an event listener without its',
    '# kernel.event_listener tag is never called, so they are declared here with',
    '# their original ids. Classes are remapped to App\\<Module>\\.',
    '#',
    '# Definitions are public because controllers still fetch several of these',
    '# by id. As each call site moves to constructor injection, drop `public`.',
    '',
];

$yaml = Yaml::dump(['parameters' => $parameters, 'services' => $services], 6, 4);
file_put_contents($outPath = $next . '/config/services_ported.yaml', implode("\n", $out) . $yaml);

printf("%d services, %d parameters -> config/services_ported.yaml\n", count($services), count($parameters));

if ($dropped !== []) {
    printf("\ndropped %d definitions:\n", count($dropped));

    foreach ($dropped as $line) {
        echo "    {$line}\n";
    }
}
