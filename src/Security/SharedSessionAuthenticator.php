<?php

declare(strict_types=1);

namespace App\Security;

use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Keeps a user logged in across the proxy boundary while both applications
 * serve traffic.
 *
 * Sharing the session store is not enough on its own. Symfony 2 wrote the
 * security token into `_security_main` with the Serializable format of its own
 * token classes; Symfony 8's token classes cannot read that payload, and the
 * reverse is equally true. So neither side reads the other's token.
 *
 * Instead each application writes a neutral key on successful login - the
 * user identifier, nothing else - and reads it when it has no session of its
 * own. This authenticator is the Symfony 8 half; the legacy half is
 * Core\UserBundle\EventListener\SharedSessionListener.
 *
 * The key is only trusted because it lives in the shared session store, which
 * only the two applications can write. It carries no credentials.
 */
final class SharedSessionAuthenticator extends AbstractAuthenticator
{
    public const SESSION_KEY = '_migration_user';

    public function supports(Request $request): ?bool
    {
        if (!$request->hasSession() || !$request->hasPreviousSession()) {
            return false;
        }

        $session = $request->getSession();

        // Only when this application has no session of its own: an existing
        // token always wins, so a logout here is not undone by a stale key.
        return $session->has(self::SESSION_KEY) && !$session->has('_security_main');
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = (string) $request->getSession()->get(self::SESSION_KEY);

        if ($identifier === '') {
            throw new AuthenticationException('The shared session carries no user identifier.');
        }

        return new SelfValidatingPassport(new UserBadge($identifier));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Continue with the request that triggered it; the user is simply
        // already logged in.
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // A stale key (the user was deleted, say) must not break the request;
        // drop it and carry on as anonymous.
        if ($request->hasSession()) {
            $request->getSession()->remove(self::SESSION_KEY);
        }

        return null;
    }
}
