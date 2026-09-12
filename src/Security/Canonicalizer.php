<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Reproduces FOS\UserBundle\Util\Canonicalizer.
 *
 * The `username_canonical` and `email_canonical` columns in the live database
 * were written with this exact transform, so lookups must use it verbatim or
 * existing users cannot be found at login.
 *
 * The parameter is deliberately wider than a string. FOSUserBundle's method was
 * untyped, and the application leans on that: usernames here are mobile numbers,
 * and enough call sites hand over an int for a `?string` hint to turn what used
 * to work into a TypeError. Since §5.39 put a listener on prePersist and
 * preUpdate, this runs on every user write, so the narrow hint would have
 * crashed saves that have nothing to do with usernames.
 */
final class Canonicalizer
{
    public static function canonicalize(int|string|null $string): ?string
    {
        if ($string === null) {
            return null;
        }

        $string = (string) $string;
        $encoding = mb_detect_encoding($string);

        return $encoding === false
            ? mb_convert_case($string, MB_CASE_LOWER)
            : mb_convert_case($string, MB_CASE_LOWER, $encoding);
    }
}
