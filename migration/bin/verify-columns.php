<?php

declare(strict_types=1);

/**
 * Compares every ported entity's column names against the legacy annotations.
 *
 * The database is the contract in a strangler migration: both applications
 * write the same tables, so a single renamed column silently splits the data.
 * schema-diff.php checks the mapping against a dump, but a dump is one tenant
 * at one point in time. This check needs no database: Doctrine 2's
 * DefaultNamingStrategy is reproduced here (implicit column = property name,
 * implicit join column = <property>_id) and applied to the legacy annotations,
 * then compared with the metadata the ported app actually loads.
 *
 * usage: php8.5 migration/bin/verify-columns.php [--report=path]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
$report = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--report=')) {
        $report = substr($arg, strlen('--report='));
    }
}

// ---- 0. bundle directory -> module, so a legacy entity file resolves to the
// class the port produced. Two bundles each define a Product; keying on the
// short name alone compares the wrong pair.
$moduleOf = [];

foreach (array_merge(
    glob("$root/src/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*Bundle.php") ?: [],
    glob("$root/src/*/*/*/*Bundle.php") ?: [],
) as $bundleFile) {
    $dir = basename(dirname($bundleFile));

    if (str_ends_with($dir, 'Bundle')) {
        $moduleOf[dirname($bundleFile)] = substr($dir, 0, -strlen('Bundle'));
    }
}

$portedClassFor = static function (string $entityFile) use ($moduleOf): ?string {
    foreach ($moduleOf as $bundleDir => $module) {
        if (str_starts_with($entityFile, $bundleDir . '/')) {
            $relative = substr($entityFile, strlen($bundleDir) + 1);
            $relative = preg_replace('#^Entity/#', '', $relative);

            return 'App\\' . $module . '\\Entity\\' . str_replace('/', '\\', substr($relative, 0, -4));
        }
    }

    return null;
};

// ---- 1. legacy entities: ported FQCN -> expected columns -------------------

$legacy = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src')) as $file) {
    if ($file->getExtension() !== 'php' || !str_contains($file->getPathname(), '/Entity/')) {
        continue;
    }

    $code = file_get_contents($file->getPathname());

    if (!preg_match('/@ORM\\\\Entity/', $code) || !preg_match('/^class\s+(\w+)/m', $code, $cm)) {
        continue;
    }

    $columns = [];

    // Each docblock immediately preceding a property declaration.
    if (preg_match_all('#/\*\*(.*?)\*/\s*(?:protected|private|public)\s+\$(\w+)#s', $code, $props, PREG_SET_ORDER)) {
        foreach ($props as $prop) {
            [, $doc, $property] = $prop;

            if (preg_match('/@ORM\\\\(OneToMany|ManyToMany)/', $doc)) {
                continue; // no column on this side
            }

            // The inverse side of a OneToOne carries mappedBy and no column.
            if (preg_match('/@ORM\\\\OneToOne\([^)]*mappedBy/s', $doc)) {
                continue;
            }

            if (preg_match('/@ORM\\\\(ManyToOne|OneToOne)/', $doc)) {
                if (preg_match('/@ORM\\\\JoinColumn\([^)]*name\s*=\s*"([^"]+)"/', $doc, $jm)) {
                    $columns[$jm[1]] = true;
                } else {
                    // DefaultNamingStrategy: <property>_id
                    $columns[$property . '_id'] = true;
                }

                continue;
            }

            if (preg_match('/@ORM\\\\Column\(/', $doc)) {
                if (preg_match('/@ORM\\\\Column\([^)]*name\s*=\s*"([^"]+)"/', $doc, $col)) {
                    $columns[$col[1]] = true;
                } else {
                    $columns[$property] = true;
                }
            }
        }
    }

    $portedClass = $portedClassFor($file->getPathname());

    if ($columns !== [] && $portedClass !== null) {
        $legacy[$portedClass] = $columns;
    }
}

printf("legacy entities parsed: %d\n", count($legacy));

// ---- 2. ported metadata ----------------------------------------------------

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();

$renamed = [];
$missing = [];
$compared = 0;

foreach ($em->getMetadataFactory()->getAllMetadata() as $meta) {
    /** @var ClassMetadata $meta */
    if (!isset($legacy[$meta->getName()])) {
        continue;
    }

    ++$compared;
    $ported = [];

    foreach ($meta->fieldMappings as $field => $mapping) {
        $ported[$mapping->columnName ?? $mapping['columnName'] ?? $field] = true;
    }

    foreach ($meta->associationMappings as $field => $mapping) {
        foreach ($mapping->joinColumns ?? $mapping['joinColumns'] ?? [] as $joinColumn) {
            $ported[$joinColumn->name ?? $joinColumn['name']] = true;
        }
    }

    foreach (array_keys($legacy[$meta->getName()]) as $column) {
        if (!isset($ported[$column])) {
            $missing[] = sprintf('%-50s %s', $meta->getName(), $column);
        }
    }
}

printf("entities compared:      %d\n", $compared);
printf("legacy columns absent from the ported mapping: %d\n", count($missing));

if ($report !== null) {
    $out = "# Column-name verification: ported mapping vs the legacy annotations\n\n"
        . "Generated by `migration/bin/verify-columns.php`. Doctrine 2's DefaultNamingStrategy\n"
        . "is reproduced from the legacy docblocks and compared with the metadata the ported\n"
        . "application loads. Anything listed is a column the legacy app reads or writes and\n"
        . "the ported app does not - i.e. data the two would not share.\n\n"
        . sprintf("- legacy entities parsed: **%d**\n- entities compared: **%d**\n- columns missing: **%d**\n\n",
            count($legacy), $compared, count($missing));

    if ($missing !== []) {
        $out .= "```\n" . implode("\n", $missing) . "\n```\n";
    } else {
        $out .= "_No differences._\n";
    }

    file_put_contents($report, $out);
    printf("wrote %s\n", $report);
}

foreach (array_slice($missing, 0, 30) as $line) {
    echo "  {$line}\n";
}

exit($missing === [] ? 0 : 1);
