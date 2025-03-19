<?php

namespace App\EventSubscriber;

use App\Service\ApiLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiRequestIdSubscriber implements EventSubscriberInterface
{
    public const REQUEST_API_ID_KEY = 'api_request_id';
    public const REQUEST_START_TIME = 'api_request_start_time';

    public function __construct(
        private readonly ApiLogger $apiLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api') || str_starts_with($request->getPathInfo(), '/api/doc')) {
            return;
        }

        $requestId = uniqid('', true);
        $request->attributes->set(self::REQUEST_API_ID_KEY, $requestId);
        $request->attributes->set(self::REQUEST_START_TIME, microtime(true));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api') || str_starts_with($request->getPathInfo(), '/api/doc')) {
            return;
        }

        $requestId = $request->attributes->get(self::REQUEST_API_ID_KEY);
        $response = $event->getResponse();
        $response->headers->set('X-Request-ID', $requestId);
        $this->apiLogger->logApiCall($request, $response);
    }
}
