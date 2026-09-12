<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withPhpSets(php85: true)
    ->withSets([
        // Doctrine 2 annotations -> PHP 8 attributes (335 legacy entities depend on this)
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        // Symfony annotations -> attributes, and the accumulated 2.8 -> 8.x API renames
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withSkip([
        // Hand-written compatibility layer, not generated from the legacy tree.
        // Rector would add property types here that clash with the untyped
        // properties the ported subclasses redeclare.
        __DIR__ . '/src/Security',
        __DIR__ . '/src/Comment',
        __DIR__ . '/src/Doctrine',
        // ... and every other hand-written replacement. Rector rewrote the
        // Twig extensions to attribute-based ones, which drops
        // ExtensionInterface and breaks their twig.extension tag.
        ...array_map(
            static fn (string $line): string => dirname(__DIR__) . '/next/' . $line,
            array_values(array_filter(array_map(
                static fn (string $line): string => trim(explode('#', $line, 2)[0]),
                file(dirname(__DIR__) . '/migration/hand-written.txt'),
            ))),
        ),
    ])
    ->withImportNames(removeUnusedImports: true);
