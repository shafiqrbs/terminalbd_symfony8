<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Reproduces FOS\UserBundle\Util\Canonicalizer.
 *
 * The `username_canonical` and `email_canonical` columns in the live database
 * were written with this exact transform, so lookups must use it verbatim or
 * existing users cannot be found at login.
 */
final class Canonicalizer
{
    public static function canonicalize(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }

        $encoding = mb_detect_encoding($string);

        return $encoding === false
            ? mb_convert_case($string, MB_CASE_LOWER)
            : mb_convert_case($string, MB_CASE_LOWER, $encoding);
    }
}
