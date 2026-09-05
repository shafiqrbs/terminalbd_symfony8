<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Writes the neutral session key SharedSessionAuthenticator reads, and clears
 * it on logout so signing out of one application signs the user out of both.
 */
final class SharedSessionListener
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    #[AsEventListener(event: InteractiveLoginEvent::class)]
    public function onLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $request->getSession()->set(
            SharedSessionAuthenticator::SESSION_KEY,
            $event->getAuthenticationToken()->getUserIdentifier(),
        );
    }

    #[AsEventListener(event: LogoutEvent::class)]
    public function onLogout(LogoutEvent $event): void
    {
        $session = $event->getRequest()?->getSession();

        $session?->remove(SharedSessionAuthenticator::SESSION_KEY);
    }
}
