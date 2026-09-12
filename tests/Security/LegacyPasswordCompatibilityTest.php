<?php

declare(strict_types=1);

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\MigratingPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

/**
 * Proves that password hashes already stored in the production `fos_user` table
 * still verify under Symfony 8, and are upgraded rather than invalidated.
 *
 * The legacy hash is reproduced here from Symfony 2's algorithm directly rather
 * than by calling Symfony 8's hasher, so this is a genuine cross-check and not
 * the framework agreeing with itself.
 */
final class LegacyPasswordCompatibilityTest extends TestCase
{
    /**
     * Symfony 2.8 MessageDigestPasswordEncoder::encodePassword(), transcribed.
     * The `sha512` shorthand in app/config/security.yml resolves to
     * sha512 / base64 / 5000 iterations.
     */
    private static function symfony2Sha512(string $raw, string $salt): string
    {
        $salted = $salt === '' ? $raw : $raw . '{' . $salt . '}';
        $digest = hash('sha512', $salted, true);

        for ($i = 1; $i < 5000; ++$i) {
            $digest = hash('sha512', $digest . $salted, true);
        }

        return base64_encode($digest);
    }

    public static function credentials(): iterable
    {
        // FOSUser generated salts as base_convert(sha1(uniqid(...)), 16, 36).
        yield 'ascii password'   => ['correct horse battery staple', 'k3j4h5g6f7d8s9a0'];
        yield 'short password'   => ['@123456', 'zz9p0kj1'];
        yield 'unicode password' => ['পাসওয়ার্ড২০২৬', 'q1w2e3r4t5y6'];
        yield 'symbols'          => ['p@$$w0rd!#%&*()', 'abc123def456'];
        yield 'empty salt'       => ['plain-no-salt', ''];
    }

    #[DataProvider('credentials')]
    public function testLegacyHashVerifiesUnderSymfony8(string $plain, string $salt): void
    {
        $legacyHash = self::symfony2Sha512($plain, $salt);

        $hasher = new MessageDigestPasswordHasher('sha512', true, 5000);

        self::assertTrue(
            $hasher->verify($legacyHash, $plain, $salt === '' ? null : $salt),
            'A hash written by Symfony 2 must verify under Symfony 8.'
        );
        self::assertFalse(
            $hasher->verify($legacyHash, $plain . 'x', $salt === '' ? null : $salt),
            'A wrong password must not verify.'
        );
    }

    #[DataProvider('credentials')]
    public function testMigratingHasherAcceptsLegacyAndRequestsRehash(string $plain, string $salt): void
    {
        $legacy  = new MessageDigestPasswordHasher('sha512', true, 5000);
        $modern  = new NativePasswordHasher(cost: 4);
        $hashers = new MigratingPasswordHasher($modern, $legacy);

        $legacyHash = self::symfony2Sha512($plain, $salt);

        self::assertTrue(
            $hashers->verify($legacyHash, $plain, $salt === '' ? null : $salt),
            'The migrating hasher must accept the legacy format.'
        );
        self::assertTrue(
            $hashers->needsRehash($legacyHash),
            'A legacy hash must be flagged for upgrade so upgradePassword() runs.'
        );
    }

    public function testRehashedPasswordNoLongerNeedsRehashAndStillVerifies(): void
    {
        $plain  = 'correct horse battery staple';
        $legacy = new MessageDigestPasswordHasher('sha512', true, 5000);
        $modern = new NativePasswordHasher(cost: 4);
        $hashers = new MigratingPasswordHasher($modern, $legacy);

        $upgraded = $hashers->hash($plain);

        self::assertFalse($hashers->needsRehash($upgraded), 'A freshly upgraded hash must be stable.');
        self::assertTrue($hashers->verify($upgraded, $plain));
        self::assertFalse($hashers->verify($upgraded, 'wrong'));
    }

    /**
     * The salt column is not decorative: without LegacyPasswordAuthenticatedUserInterface
     * Symfony passes null and every legacy verification silently fails.
     */
    public function testSaltIsRequiredForLegacyVerification(): void
    {
        $plain = 'correct horse battery staple';
        $salt  = 'k3j4h5g6f7d8s9a0';
        $hash  = self::symfony2Sha512($plain, $salt);

        $hasher = new MessageDigestPasswordHasher('sha512', true, 5000);

        self::assertTrue($hasher->verify($hash, $plain, $salt));
        self::assertFalse(
            $hasher->verify($hash, $plain, null),
            'Dropping the salt must fail - proving the salt has to be carried through.'
        );
    }
}
