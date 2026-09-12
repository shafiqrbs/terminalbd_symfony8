<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\Canonicalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The `username_canonical` / `email_canonical` columns in production were
 * written by FOS\UserBundle\Util\Canonicalizer. If this transform diverges,
 * UserProvider stops finding existing users and every login fails.
 */
final class CanonicalizerTest extends TestCase
{
    /** FOSUser: mb_convert_case($s, MB_CASE_LOWER, mb_detect_encoding($s)) */
    private static function fosUserCanonicalize(?string $s): ?string
    {
        return $s === null ? null : mb_convert_case($s, MB_CASE_LOWER, mb_detect_encoding($s));
    }

    public static function inputs(): iterable
    {
        yield 'mixed case'   => ['AdminUser'];
        yield 'already low'  => ['adminuser'];
        yield 'email'        => ['Support@LazyCoders.CO'];
        yield 'with digits'  => ['Shop_042'];
        yield 'unicode'      => ['ব্যবহারকারী'];
        yield 'turkish i'    => ['ISTANBUL'];
        yield 'empty'        => [''];
    }

    #[DataProvider('inputs')]
    public function testMatchesFosUserBundle(string $input): void
    {
        self::assertSame(
            self::fosUserCanonicalize($input),
            Canonicalizer::canonicalize($input),
            'Canonicalization must match FOSUserBundle byte-for-byte.'
        );
    }

    public function testNullPassesThrough(): void
    {
        self::assertNull(Canonicalizer::canonicalize(null));
    }
}
