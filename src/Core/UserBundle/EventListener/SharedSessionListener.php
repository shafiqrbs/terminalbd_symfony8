<?php

namespace Core\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * The legacy half of the strangler session bridge.
 *
 * While both applications serve traffic, a user who logs in on one side must
 * stay logged in on the other. Sharing the session store is necessary but not
 * sufficient: Symfony 2 serialises its security token in a format Symfony 8
 * cannot read, and the reverse is also true.
 *
 * So each side writes one neutral key - the user identifier - and reads it
 * when it has no token of its own. The Symfony 8 half is
 * App\Security\SharedSessionAuthenticator.
 *
 * INERT until registered. Enabling the bridge is a deliberate deploy step,
 * because it also means pointing this application's sessions at the shared
 * database table; see "Session bridge" in migration/MIGRATION-PLAN.md.
 */
class SharedSessionListener
{
    const SESSION_KEY = '_migration_user';

    private $tokenStorage;

    private $userProvider;

    private $firewallName;

    public function __construct(TokenStorageInterface $tokenStorage, UserProviderInterface $userProvider, $firewallName = 'main')
    {
        $this->tokenStorage = $tokenStorage;
        $this->userProvider = $userProvider;
        $this->firewallName = $firewallName;
    }

    /**
     * Publishes the identifier for the other application to pick up.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $user = $event->getAuthenticationToken()->getUser();

        $request->getSession()->set(
            self::SESSION_KEY,
            is_object($user) ? $user->getUsername() : (string) $user
        );
    }

    /**
     * Adopts a session started by the Symfony 8 application.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (null !== $this->tokenStorage->getToken()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasSession() || !$request->hasPreviousSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session->has(self::SESSION_KEY)) {
            return;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($session->get(self::SESSION_KEY));
        } catch (AuthenticationException $e) {
            // Stale key - the user was removed. Drop it and stay anonymous.
            $session->remove(self::SESSION_KEY);

            return;
        }

        $this->tokenStorage->setToken(
            new UsernamePasswordToken($user, null, $this->firewallName, $user->getRoles())
        );
    }
}
