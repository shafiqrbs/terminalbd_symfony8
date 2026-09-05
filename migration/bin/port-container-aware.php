<?php

declare(strict_types=1);

/**
 * The three container-awareness constructs Symfony removed.
 *
 *   ContainerAwareTrait     (Symfony 7) - the 19 Excel importers used it for
 *                           one thing, reaching `doctrine`; they take a
 *                           ManagerRegistry through an autowired setter now,
 *                           which also drops the setContainer() calls their
 *                           service definitions made.
 *   ContainerAware          (Symfony 5) - base class of the two menu builders.
 *   ContainerAwareCommand   (Symfony 5) - base class of the four commands.
 *                           They become plain Command subclasses carrying
 *                           #[AsCommand], since Symfony no longer reads the
 *                           name from configure() at registration time.
 *
 * getContainer()->get() and $this->container->get() calls left behind are
 * normalised so port-service-locator.php can inject them.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-container-aware.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['files' => 0, 'call sites' => 0, 'ContainerAware' => 0, 'commands' => 0];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if (!str_contains($code, 'ContainerAware')) {
        continue;
    }

    $original = $code;

    // ContainerAwareCommand -> Command, with the name moved to #[AsCommand].
    if (str_contains($code, 'extends ContainerAwareCommand')) {
        $code = str_replace(
            "use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;",
            "use Symfony\Component\Console\Attribute\AsCommand;\nuse Symfony\Component\Console\Command\Command;",
            $code,
        );
        $code = str_replace('extends ContainerAwareCommand', 'extends Command', $code);

        if (!str_contains($code, '#[AsCommand')) {
            preg_match("/setName\(\s*'([^']+)'\s*\)/", $code, $nameMatch);
            preg_match("/setDescription\(\s*'([^']*)'\s*\)/", $code, $descMatch);

            if (isset($nameMatch[1])) {
                $attribute = "#[AsCommand(name: '" . $nameMatch[1] . "'"
                    . (isset($descMatch[1]) ? ", description: '" . str_replace("'", "\\'", $descMatch[1]) . "'" : '')
                    . ")]\n";
                $code = preg_replace('/(class\s+\w+\s+extends\s+Command)/', $attribute . '$1', $code, 1);
            }
        }

        // Symfony 8's Command declares return types on its hooks; an
        // override without them is a fatal incompatible-signature error, and
        // execute() must return an exit code.
        $code = preg_replace('/(protected function (?:configure|initialize|interact)\([^)]*\))(\s*\n?\s*\{)/', '$1: void$2', $code);
        $code = preg_replace('/(protected function execute\([^)]*\))(\s*\n?\s*\{)/', '$1: int$2', $code);

        if (preg_match('/protected function execute\([^)]*\): int\s*\n?\s*\{/', $code, $em, PREG_OFFSET_CAPTURE)) {
            $bodyStart = $em[0][1] + strlen($em[0][0]);
            $depth = 1;
            $i = $bodyStart;

            for ($len = strlen($code); $i < $len && $depth > 0; ++$i) {
                if ($code[$i] === '{') {
                    ++$depth;
                } elseif ($code[$i] === '}') {
                    --$depth;
                }
            }

            $body = substr($code, $bodyStart, $i - $bodyStart - 1);

            if (!preg_match('/\breturn\b/', $body)) {
                $code = substr($code, 0, $i - 1)
                    . "\n        return Command::SUCCESS;\n    "
                    . substr($code, $i - 1);
            }
        }

        ++$stats['commands'];
    }

    // ContainerAware (the base class) went in Symfony 5; nothing inherits from
    // it any more, and the two menu builders take their dependencies already.
    if (preg_match('/extends ContainerAware\b(?!Command)/', $code)) {
        $code = preg_replace('/\s+extends ContainerAware\b(?!Command)/', '', $code);
        $code = str_replace("use Symfony\Component\DependencyInjection\ContainerAware;\n", '', $code);
        ++$stats['ContainerAware'];
    }

    // Normalise so the service-locator pass sees a single shape.
    $code = str_replace('$this->getContainer()->get(', '$this->container->get(', $code);

    $code = str_replace("use Symfony\Component\DependencyInjection\ContainerAwareTrait;\n", '', $code);
    $hasTrait = str_contains($original, 'use ContainerAwareTrait;');
    $code = preg_replace('/\n\s*use ContainerAwareTrait;\n/', "\n", $code);

    $count = 0;
    $code = str_replace("\$this->container->get('doctrine')", '$this->doctrine', $code, $count);
    $stats['call sites'] += $count;

    if ($hasTrait && !str_contains($code, 'use Doctrine\Persistence\ManagerRegistry;')) {
        $code = preg_replace('/^(namespace [^;]+;\n)/m', "$1\nuse Doctrine\\Persistence\\ManagerRegistry;\n", $code, 1);
    }

    if ($hasTrait && !str_contains($code, 'public function setDoctrine(')) {
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

foreach ($stats as $label => $count) {
    printf("%-16s %d\n", $label, $count);
}
