<?php

declare(strict_types=1);

namespace App\Tool\Image;

use Symfony\Component\Asset\Packages;

/**
 * Reproduces the image.handling service of gregwar/image-bundle, with the
 * bundle's own defaults: a `cache` directory under the web root, resolved to
 * a URL through the asset packages.
 */
class ImageHandling
{
    public function __construct(
        private readonly Packages $packages,
        private readonly string $publicDir,
        private readonly string $cacheDirectory = 'cache',
        private readonly bool $throwException = false,
        private readonly ?string $fallbackImage = null,
    ) {
    }

    public function open($file): ImageHandler
    {
        return $this->createInstance($file);
    }

    public function create($width, $height): ImageHandler
    {
        return $this->createInstance(null, $width, $height);
    }

    private function createInstance($file, $width = null, $height = null): ImageHandler
    {
        $image = new ImageHandler($file, $width, $height, $this->throwException, $this->fallbackImage);

        $image->setCacheDir($this->cacheDirectory);
        $image->setActualCacheDir($this->publicDir . '/' . $this->cacheDirectory);
        $image->setFileCallback(fn (string $generated): string => $this->packages->getUrl($generated));

        return $image;
    }
}
