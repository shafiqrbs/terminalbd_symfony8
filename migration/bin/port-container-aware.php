<?php

declare(strict_types=1);

/**
 * ContainerAwareTrait was removed in Symfony 7.
 *
 * The 19 Excel importers used it for one thing: reaching the `doctrine`
 * service. They take a ManagerRegistry through an autowired setter instead,
 * which also drops the setContainer() calls their service definitions made.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-container-aware.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['files' => 0, 'call sites' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, 'ContainerAwareTrait')) {
        continue;
    }

    $original = $code;

    $code = str_replace("use Symfony\Component\DependencyInjection\ContainerAwareTrait;\n", '', $code);
    $code = preg_replace('/\n\s*use ContainerAwareTrait;\n/', "\n", $code);

    $count = 0;
    $code = str_replace("\$this->container->get('doctrine')", '$this->doctrine', $code, $count);
    $stats['call sites'] += $count;

    if (!str_contains($code, 'use Doctrine\Persistence\ManagerRegistry;')) {
        $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse Doctrine\\Persistence\\ManagerRegistry;\n", $code, 1);
    }

    if (!str_contains($code, 'public function setDoctrine(')) {
        $code = preg_replace(
            '/(class\s+\w+[^{]*\{\n)/',
            "$1"
            . "    protected ?ManagerRegistry \$doctrine = null;\n\n"
            . "    #[\\Symfony\\Contracts\\Service\\Attribute\\Required]\n"
            . "    public function setDoctrine(ManagerRegistry \$doctrine): void\n"
            . "    {\n"
            . "        \$this->doctrine = \$doctrine;\n"
            . "    }\n\n",
            $code,
            1,
        );
    }

    if ($code !== $original) {
        ++$stats['files'];

        if (!$dryRun) {
            file_put_contents($path, $code);
        }
    }
}

printf("files:      %d\ncall sites: %d\n", $stats['files'], $stats['call sites']);
