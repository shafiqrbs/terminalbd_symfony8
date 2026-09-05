<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\SharedSessionAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * The bridge that keeps a user logged in across the proxy boundary.
 *
 * What matters here is when it does *not* fire: a stale key must never
 * resurrect a session the user closed on this side, or override a session
 * this application already has.
 */
final class SharedSessionAuthenticatorTest extends TestCase
{
    private function request(array $sessionData, bool $previousSession = true): Request
    {
        $session = new Session(new MockArraySessionStorage());

        foreach ($sessionData as $key => $value) {
            $session->set($key, $value);
        }

        $request = new Request();
        $request->setSession($session);

        if ($previousSession) {
            $request->cookies->set($session->getName(), 'an-existing-session');
        }

        return $request;
    }

    public function testAdoptsASessionStartedByTheLegacyApplication(): void
    {
        $authenticator = new SharedSessionAuthenticator();
        $request = $this->request([SharedSessionAuthenticator::SESSION_KEY => 'shopkeeper']);

        self::assertTrue($authenticator->supports($request));

        $passport = $authenticator->authenticate($request);
        $badge = $passport->getBadge(UserBadge::class);

        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame('shopkeeper', $badge->getUserIdentifier());
    }

    public function testATokenOnThisSideAlwaysWins(): void
    {
        $authenticator = new SharedSessionAuthenticator();

        $request = $this->request([
            SharedSessionAuthenticator::SESSION_KEY => 'shopkeeper',
            '_security_main' => 'a serialized token',
        ]);

        self::assertFalse($authenticator->supports($request));
    }

    public function testIgnoresARequestWithoutTheKey(): void
    {
        $authenticator = new SharedSessionAuthenticator();

        self::assertFalse($authenticator->supports($this->request([])));
    }

    public function testIgnoresAFirstRequestWithNoSessionCookie(): void
    {
        $authenticator = new SharedSessionAuthenticator();

        $request = $this->request(
            [SharedSessionAuthenticator::SESSION_KEY => 'shopkeeper'],
            previousSession: false,
        );

        self::assertFalse($authenticator->supports($request));
    }

    public function testAnEmptyKeyIsRejectedRatherThanLoggingSomeoneIn(): void
    {
        $authenticator = new SharedSessionAuthenticator();
        $request = $this->request([SharedSessionAuthenticator::SESSION_KEY => '']);

        $this->expectException(AuthenticationException::class);

        $authenticator->authenticate($request);
    }

    public function testAStaleKeyIsDroppedOnFailure(): void
    {
        $authenticator = new SharedSessionAuthenticator();
        $request = $this->request([SharedSessionAuthenticator::SESSION_KEY => 'deleted-user']);

        self::assertNull($authenticator->onAuthenticationFailure($request, new AuthenticationException()));
        self::assertFalse($request->getSession()->has(SharedSessionAuthenticator::SESSION_KEY));
    }
}
