<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Port of Xiidea\Bundle\DomainBundle\EventListener\DomainAttributeListener.
 *
 * Terminalbd is multi-tenant by subdomain, so generated URLs have to carry the
 * tenant of the current request. This copies the `subdomain` and `domain`
 * request attributes into the router context, and restores the parent
 * request's values when a sub-request finishes - otherwise a rendered
 * sub-request leaks its tenant into the rest of the response's URLs.
 */
final class TenantRoutingListener implements EventSubscriberInterface
{
    private const ATTRIBUTES = ['subdomain', 'domain'];

    public function __construct(
        private readonly RequestContextAwareInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run after the router has matched, so the attributes exist.
            KernelEvents::REQUEST => [['onKernelRequest', 16]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->apply($event->getRequest());
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if (($parent = $this->requestStack->getParentRequest()) !== null) {
            $this->apply($parent);
        }
    }

    private function apply(Request $request): void
    {
        foreach (self::ATTRIBUTES as $name) {
            $value = $request->attributes->get($name);
            if ($value !== null && $value !== '') {
                $this->router->getContext()->setParameter($name, $value);
            }
        }
    }
}
