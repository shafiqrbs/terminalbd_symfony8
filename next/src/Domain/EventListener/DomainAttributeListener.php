<?php

namespace App\Domain\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;

class DomainAttributeListener implements EventSubscriberInterface
{
    private $attributeNames = ['subdomain', 'domain'];
    /**
     * @param RequestContextAwareInterface $router
     * @param RequestStack                 $requestStack
     */
    public function __construct(private readonly ?RequestContextAwareInterface $router = null, private readonly ?RequestStack $requestStack = null)
    {
    }
    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (null === $this->attributeNames) {
            return;
        }
        $request = $event->getRequest();
        foreach ($this->attributeNames as $attributeName) {
            $this->setRouterContext($request, $attributeName);
        }
    }
    /**
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if (null === $this->requestStack) {
            return; // removed when requestStack is required
        }

        if (null !== $parentRequest = $this->requestStack->getParentRequest()) {
            foreach ($this->attributeNames as $attributeName) {
                $this->setRouterContext($parentRequest, $attributeName);
            }
        }
    }

    private function setRouterContext(Request $request, $attributeName)
    {
        if (null !== $this->router) {
            if ($attr = $request->attributes->get($attributeName)) {
                $this->router->getContext()->setParameter($attributeName, $attr);
            }
        }
    }
    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after the Router to have access to the _locale
            KernelEvents::REQUEST => [['onKernelRequest', 16]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }
}