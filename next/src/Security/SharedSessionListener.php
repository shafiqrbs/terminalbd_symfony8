<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Publishes the neutral session key the other two applications read.
 *
 * This tree already had the READING half - SharedSessionAuthenticator - and not
 * the writing half, so the bridge only ever worked in one direction. Signing in
 * through the API carried into next/, and signing in through next/ carried
 * nowhere: the API found no key, showed its own login, and the user logged in
 * twice. Landing on `/` made that the normal path, because `/` is not a ported
 * screen and the proxy hands it here.
 *
 * The API's listener is the same code; legacy's is
 * Core\UserBundle\EventListener\SharedSessionListener. All three write the same
 * key so that any one of them can start the session.
 */
final class SharedSessionListener
{
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
        $event->getRequest()?->getSession()?->remove(SharedSessionAuthenticator::SESSION_KEY);
    }
}
