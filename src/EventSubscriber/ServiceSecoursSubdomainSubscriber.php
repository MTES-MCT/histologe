<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ServiceSecoursSubdomainSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 31],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        //        if (!$event->isMainRequest()) {
        //            return;
        //        }
        //
        //        $request = $event->getRequest();
        //        $host = $request->getHost();
        //
        //        if (!str_starts_with($host, 'service-secours.')) {
        //            return;
        //        }
        //
        //        $routeName = $request->attributes->get('_route');
        //
        //        // Autorise uniquement les routes du ServiceSecoursController (préfixées par "service_secours_")
        //        // et les routes système nécessaires (_wdt, _profiler, etc.)
        //        if (null !== $routeName && (
        //            str_starts_with($routeName, 'service_secours_')
        //            || str_starts_with($routeName, '_')
        //        )) {
        //            return;
        //        }
        //
        //        throw new NotFoundHttpException();
    }
}
