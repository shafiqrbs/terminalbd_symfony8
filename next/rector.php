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
    ])
    ->withImportNames(removeUnusedImports: true);
