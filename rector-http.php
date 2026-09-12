<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony30\Rector\MethodCall\FormTypeInstanceToClassConstRector;

/**
 * HTTP-layer modernisation, kept separate from rector.php (which owns the
 * entity graph). This one is destructive to method bodies, so it is run
 * deliberately rather than as part of port.sh's entity rebuild.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip(array_merge(
        [
            // Hand-written compatibility layer, owned by no generator.
            __DIR__ . '/src/Security',
            __DIR__ . '/src/Comment',
            __DIR__ . '/src/Doctrine',
        ],
        // ... and every other hand-written replacement; see
        // migration/hand-written.txt.
        array_map(
            static fn (string $line): string => __DIR__ . '/' . $line,
            array_values(array_filter(array_map(
                static fn (string $line): string => trim(explode('#', $line, 2)[0]),
                file(dirname(__DIR__) . '/migration/hand-written.txt'),
            ))),
        ),
        // FormTypeInstanceToClassConstRector converts `createForm(new XType())`
        // to `createForm(XType::class)` correctly, but crashes on the sites that
        // pass constructor dependencies - and a crash skips the whole file. Those
        // files are listed by mark-manual-forms.php (which runs first) and skipped
        // here, so the rule still converts every mechanical site elsewhere.
        file_exists($manual = dirname(__DIR__) . '/migration/contract/manual-form-files.txt')
            ? array_filter(array_map('trim', file($manual)))
            : [],
        // The entity graph is settled by rector.php. Body-level rules here
        // (constructor promotion, readonly properties) would break Doctrine
        // mapping outright. A '/src/*/Entity' glob is NOT honoured by
        // withSkip, so the directories are expanded explicitly.
        glob(__DIR__ . '/src/*/Entity') ?: [],
        glob(__DIR__ . '/src/*/Repository') ?: [],
        glob(__DIR__ . '/src/*/Entity/Repository') ?: [],
    ))
    ->withPhpSets(php85: true)
    ->withComposerBased(symfony: true, doctrine: true, twig: true)
    ->withSets([
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withImportNames(removeUnusedImports: true);
