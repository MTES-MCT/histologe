<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ApiLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api_limiter')]
        private readonly RateLimiterFactory $apiLimiter,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
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

        $limiter = $this->apiLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (false === $limit->isAccepted()) {
            $retryInSeconds = $limit->getRetryAfter()->getTimestamp() - time();

            $response = new JsonResponse([
                'error' => 'Nombre de requêtes dépassées pour votre adresse IP.',
                'message' => 'Veuillez réessayer dans '.$retryInSeconds.' secondes.',
                'status' => Response::HTTP_TOO_MANY_REQUESTS,
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
            $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
            $response->headers->set('Retry-After', (string) $retryInSeconds);

            $event->setResponse($response);
        }
    }
}
