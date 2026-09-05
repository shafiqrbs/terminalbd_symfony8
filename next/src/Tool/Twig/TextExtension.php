<?php

declare(strict_types=1);

namespace App\Tool\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Replaces twig/extensions' Twig_Extensions_Extension_Text, which app/config
 * registered as twig.extension.text. The package is abandoned and has no Twig 3
 * release; only the `truncate` filter is used (one template), reproduced here
 * with the original semantics.
 */
final class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', $this->truncate(...), ['needs_environment' => true]),
        ];
    }

    public function truncate(
        Environment $env,
        ?string $value,
        int $length = 30,
        bool $preserve = false,
        string $separator = '...',
    ): ?string {
        if ($value === null) {
            return null;
        }

        $charset = $env->getCharset() ?? 'UTF-8';

        if (mb_strlen($value, $charset) <= $length) {
            return $value;
        }

        if ($preserve) {
            // Cut on the first space after the limit, as the original did.
            if (false === $breakpoint = mb_strpos($value, ' ', $length, $charset)) {
                return $value;
            }

            $length = $breakpoint;
        }

        return rtrim(mb_substr($value, 0, $length, $charset)) . $separator;
    }
}
