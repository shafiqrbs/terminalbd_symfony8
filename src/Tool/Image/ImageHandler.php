<?php

declare(strict_types=1);

namespace App\Tool\Image;

use Gregwar\Image\Image;

/**
 * Reproduces Gregwar\ImageBundle\ImageHandler.
 *
 * gregwar/image-bundle has no Symfony 8 release - its DI extension still
 * declares load() without a return type, which is a fatal error on boot - but
 * the underlying gregwar/image library works. The bundle was thin: this class
 * plus a service and a Twig function, all reproduced here.
 */
class ImageHandler extends Image
{
    /** @var callable|null */
    protected $fileCallback = null;

    public function __construct(
        $originalFile = null,
        $width = null,
        $height = null,
        ?bool $throwException = null,
        $fallbackImage = null,
    ) {
        parent::__construct($originalFile, $width, $height);

        $this->useFallback(!$throwException);
        $this->setFallback($fallbackImage);
    }

    public function setFileCallback(?callable $fileCallback): void
    {
        $this->fileCallback = $fileCallback;
    }

    /**
     * The generated file is under the public directory; the callback turns it
     * into a URL through the asset packages, as the bundle did.
     */
    protected function getFilename($filename)
    {
        $callback = $this->fileCallback;

        if (null === $callback || str_starts_with((string) $filename, '/')) {
            return $filename;
        }

        return $callback($filename);
    }
}
