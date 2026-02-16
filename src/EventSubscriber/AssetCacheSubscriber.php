<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AssetCacheSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -200],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethodCacheable()) {
            return;
        }

        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/assets/')) {
            return;
        }

        $response = $event->getResponse();
        if (!$response->isSuccessful()) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->headers->addCacheControlDirective('immutable', true);
    }
}
