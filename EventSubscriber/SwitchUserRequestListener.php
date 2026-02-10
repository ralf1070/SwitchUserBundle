<?php

namespace KimaiPlugin\SwitchUserBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;

/**
 * Wraps Symfony's SwitchUserListener to restrict it to web requests only.
 * Without this, the listener would fire on API requests too, since it's
 * registered as a global kernel.request listener (not via firewall config).
 */
final class SwitchUserRequestListener
{
    public function __construct(private SwitchUserListener $inner)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();

        if (str_starts_with($path, '/api/') || $path === '/api') {
            return;
        }

        ($this->inner)($event);
    }
}
