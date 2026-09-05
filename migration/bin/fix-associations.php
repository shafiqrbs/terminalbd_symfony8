<?php

declare(strict_types=1);

/**
 * The broken bidirectional associations catalogued in §5.6 of the plan.
 *
 * 48 references point at a field that does not exist on the other entity.
 * They are broken in the legacy tree too - Doctrine 2 resolved the inverse
 * side lazily, so they only ever failed when something touched them, which
 * nothing did. Doctrine 3 validates them up front.
 *
 * The fix is the plan's second option, "make the relation unidirectional",
 * which is what the code does today:
 *
 *   inversedBy pointing nowhere -> dropped. The owning side keeps its column
 *       and its behaviour; only the unusable back-reference goes.
 *   mappedBy pointing nowhere   -> the whole association attribute is dropped
 *       and the property is left as plain PHP. Today reading such a collection
 *       throws; afterwards it is an empty collection, so callers keep working.
 *
 * Adding the missing owning side instead would mean inventing a foreign key
 * column that the shared database does not have.
 *
 * Idempotent: run it until the validator is clean.
 * usage: php8.5 migration/bin/fix-associations.php [--dry-run]
 */

require dirname(__DIR__, 2) . '/next/vendor/autoload.php';

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

(new Dotenv())->bootEnv($root . '/next/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$validator = new SchemaValidator($em);

$fileOf = static function (string $class) use ($root): ?string {
    if (!str_starts_with($class, 'App\\')) {
        return null;
    }

    $path = $root . '/next/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';

    return is_file($path) ? $path : null;
};

/**
 * Returns [start, end] of the attribute block that immediately precedes the
 * declaration of $property, skipping any comments between the two. Editing
 * "the attributes within N characters" is not safe here: entities declare
 * several associations in a row and the wrong one gets rewritten.
 */
function attributeBlock(string $code, string $property): ?array
{
    if (!preg_match('/^[ \t]*(?:private|protected|public)\s+\$' . preg_quote($property, '/') . '\s*[;=]/m', $code, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $lines = explode("\n", substr($code, 0, $m[0][1]));

    // substr() stops on the property's own line, so the split leaves an empty
    // trailing element; drop it or the walk starts on nothing.
    if (end($lines) === '') {
        array_pop($lines);
    }

    $index = count($lines) - 1;

    // Walk back over comment lines.
    while ($index >= 0 && preg_match('#^\s*(//|/\*|\*)#', $lines[$index])) {
        --$index;
    }

    $blockEnd = $index;

    // Then over the attribute lines.
    while ($index >= 0 && preg_match('/^\s*#\[/', $lines[$index])) {
        --$index;
    }

    if ($index === $blockEnd) {
        return null; // no attributes on this property
    }

    $offset = 0;

    for ($i = 0; $i <= $index; ++$i) {
        $offset += strlen($lines[$i]) + 1;
    }

    $blockLength = 0;

    for ($i = $index + 1; $i <= $blockEnd; ++$i) {
        $blockLength += strlen($lines[$i]) + 1;
    }

    return [$offset, $offset + $blockLength];
}

$stats = ['inversedBy dropped' => 0, 'associations dropped' => 0, 'unhandled' => 0];
$unhandled = [];

foreach ($em->getMetadataFactory()->getAllMetadata() as $meta) {
    foreach ($validator->validateClass($meta) as $problem) {
        // "The association A#field refers to the inverse side field B#other
        //  which does not exist."
        if (preg_match('/The association ([\w\\\\]+)#(\w+) refers to the inverse side field [\w\\\\]+#(\w+) which does not exist/', $problem, $m)) {
            $path = $fileOf($m[1]);

            if ($path === null) {
                continue;
            }

            $code = file_get_contents($path);
            $property = $m[2];

            $block = attributeBlock($code, $property);

            if ($block === null) {
                ++$stats['unhandled'];
                $unhandled[] = $problem;

                continue;
            }

            [$from, $to] = $block;
            $text = substr($code, $from, $to - $from);
            $updated = preg_replace('/,\s*inversedBy:\s*\'[^\']*\'/', '', $text);
            $updated = preg_replace('/inversedBy:\s*\'[^\']*\'\s*,\s*/', '', $updated);

            if ($updated !== $text) {
                ++$stats['inversedBy dropped'];
                $code = substr($code, 0, $from) . $updated . substr($code, $to);

                if (!$dryRun) {
                    file_put_contents($path, $code);
                }
            }

            continue;
        }

        // "The association A#field refers to the owning side field B#other
        //  which does not exist." (or "is not defined as association")
        if (preg_match('/The association ([\w\\\\]+)#(\w+) refers to the owning side field [\w\\\\]+#(\w+) which (?:does not exist|is not defined as association)/', $problem, $m)) {
            $path = $fileOf($m[1]);

            if ($path === null) {
                continue;
            }

            $code = file_get_contents($path);
            $property = $m[2];

            $block = attributeBlock($code, $property);

            if ($block !== null) {
                [$from, $to] = $block;
                $note = "    // Was a OneToMany mappedBy '{$m[3]}', a field that does not exist on\n"
                    . "    // the other side - in the legacy tree either, so reading this collection\n"
                    . "    // threw. Left as a plain property, which reads as empty, so callers keep\n"
                    . "    // working. See migration/contract/broken-associations.md.\n";
                $code = substr($code, 0, $from) . $note . substr($code, $to);
                ++$stats['associations dropped'];

                if (!$dryRun) {
                    file_put_contents($path, $code);
                }

                continue;
            }
        }

        // "The mappings A#x and B#y are inconsistent with each other."
        // ColorSize#itemColor claims to be the inverse of Item#color, but
        // Item#color points at ItemColor - a different entity. Same defect
        // class as the rest: a back-reference to a field that is not the
        // other half of this relation.
        if (preg_match('/The mappings ([\w\\\\]+)#(\w+) and ([\w\\\\]+)#(\w+) are inconsistent/', $problem, $m)) {
            $mapping = $meta->associationMappings[$m[2]] ?? null;
            $targetMeta = $mapping !== null
                ? $em->getMetadataFactory()->getMetadataFor($mapping->targetEntity ?? $mapping['targetEntity'])
                : null;
            $targetField = $mapping->mappedBy ?? $mapping['mappedBy'] ?? null;
            $targetMapping = $targetField !== null ? ($targetMeta->associationMappings[$targetField] ?? null) : null;
            $pointsBack = $targetMapping !== null
                && ($targetMapping->targetEntity ?? $targetMapping['targetEntity']) === $meta->getName();

            if (!$pointsBack) {
                $path = $fileOf($m[1]);
                $block = $path === null ? null : attributeBlock(file_get_contents($path), $m[2]);

                if ($path !== null && $block !== null) {
                    $code = file_get_contents($path);
                    [$from, $to] = $block;
                    $other = $targetMapping !== null ? ($targetMapping->targetEntity ?? $targetMapping['targetEntity']) : 'another entity';
                    $note = "    // Was a OneToMany mappedBy '{$targetField}', but that field on the other\n"
                        . "    // side points at {$other}, not at this entity, so the relation could\n"
                        . "    // never load. Left as a plain property. See migration/contract/\n"
                        . "    // broken-associations.md.\n";
                    $code = substr($code, 0, $from) . $note . substr($code, $to);
                    ++$stats['associations dropped'];

                    if (!$dryRun) {
                        file_put_contents($path, $code);
                    }

                    continue;
                }
            }
        }

        ++$stats['unhandled'];
        $unhandled[] = $problem;
    }
}

foreach ($stats as $label => $count) {
    printf("%-22s %d\n", $label, $count);
}

foreach (array_unique($unhandled) as $problem) {
    echo "  unhandled: {$problem}\n";
}
