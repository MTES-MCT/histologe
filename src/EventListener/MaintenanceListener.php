<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MaintenanceListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
        private RouterInterface $router,
        #[Autowire(env: 'MAINTENANCE_ENABLE')]
        private bool $maintenanceEnable,
    ) {
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        if ($this->maintenanceEnable) {
            $request = $requestEvent->getRequest();
            if (str_starts_with($request->getPathInfo(), '/api') && !str_starts_with($request->getPathInfo(), '/api/doc')) {
                $this->jsonResponse($requestEvent);
            }

            if ($this->shouldLogout()) {
                $this->redirect('app_logout', $requestEvent);
            }

            if ($this->shouldRedirectToHome($requestEvent)) {
                $this->redirect('home', $requestEvent);
            }
        }
    }

    private function shouldLogout(): bool
    {
        return null !== $this->tokenStorage->getToken() && !$this->authorizationChecker->isGranted(User::ROLE_ADMIN);
    }

    private function shouldRedirectToHome(RequestEvent $requestEvent): bool
    {
        $uri = $requestEvent->getRequest()->getRequestUri();

        return (str_starts_with($uri, '/signalement')
                || str_starts_with($uri, '/suivre-mon-signalement')
                || str_starts_with($uri, '/mot-de-pass-perdu')
                || str_starts_with($uri, '/contact')
                || str_starts_with($uri, '/activation'))
            && !$this->authorizationChecker->isGranted(User::ROLE_ADMIN);
    }

    private function redirect(string $routeName, RequestEvent $requestEvent): void
    {
        $route = $this->router->generate($routeName);
        $response = new RedirectResponse($route);
        $requestEvent->setResponse($response);
    }

    private function jsonResponse(RequestEvent $requestEvent): void
    {
        $response = new JsonResponse(['error' => 'Service indisponible', 'message' => 'Maintenance en cours'], Response::HTTP_SERVICE_UNAVAILABLE);
        $requestEvent->setResponse($response);
    }
}
