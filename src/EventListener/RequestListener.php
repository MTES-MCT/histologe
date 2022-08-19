<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class RequestListener
{
    public function __construct(
        private TokenStorage $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ('login_creation_pass' !== $event->getRequest()->get('_route')) {
                $user = $token->getUser();
                if (!$user->getPassword() || User::STATUS_INACTIVE === $user->getStatut()) {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login_creation_pass')));
                }
            }
        }
    }
}
