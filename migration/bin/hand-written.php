<?php

declare(strict_types=1);

/**
 * The list of hand-written files under next/src, shared by the pipeline steps
 * that regenerate module directories. See migration/hand-written.txt.
 *
 * @return array<string, true> keys are paths relative to next/
 */
function handWrittenFiles(): array
{
    static $files = null;

    if ($files !== null) {
        return $files;
    }

    $files = [];
    $manifest = dirname(__DIR__) . '/hand-written.txt';

    foreach (file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim(explode('#', $line, 2)[0]);

        if ($line !== '') {
            $files[$line] = true;
        }
    }

    return $files;
}
