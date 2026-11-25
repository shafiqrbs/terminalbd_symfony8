<?php

declare(strict_types=1);

namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Adds ?v=<modified time> to CSS and JS URLs so a browser cannot reuse an old
 * copy. Other paths are left alone: templates and code also use asset() for
 * folders and generated images, which a query string could break.
 */
final class FileVersionStrategy implements VersionStrategyInterface
{
    public function __construct(private readonly string $publicDir)
    {
    }

    public function getVersion(string $path): string
    {
        if (!preg_match('/\.(css|js)$/i', $path)) {
            return '';
        }

        $file = $this->publicDir.'/'.ltrim($path, '/');

        return is_file($file) ? (string) filemtime($file) : '';
    }

    public function applyVersion(string $path): string
    {
        $version = $this->getVersion($path);

        return '' === $version ? $path : $path.'?v='.$version;
    }
}
