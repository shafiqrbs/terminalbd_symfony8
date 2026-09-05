<?php

declare(strict_types=1);

namespace App\Tool\Twig;

use App\Tool\Image\ImageHandling;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The image() / new_image() / web_image() functions gregwar/image-bundle
 * provided. 174 templates call image(...).resize(...).
 */
final class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageHandling $imageHandling,
        private readonly string $publicDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('image', $this->image(...), ['is_safe' => ['html']]),
            new TwigFunction('new_image', $this->newImage(...), ['is_safe' => ['html']]),
            new TwigFunction('web_image', $this->webImage(...), ['is_safe' => ['html']]),
        ];
    }

    public function image($path)
    {
        return $this->imageHandling->open($path);
    }

    public function newImage($width, $height)
    {
        return $this->imageHandling->create($width, $height);
    }

    public function webImage($path)
    {
        return $this->imageHandling->open($this->publicDir . '/' . $path);
    }
}
