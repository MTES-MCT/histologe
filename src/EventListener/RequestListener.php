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
    private TokenStorage $tokenStorage;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(TokenStorage $tokenStorage, UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ($event->getRequest()->get('_route') !== 'login_creation_pass') {
                $user = $token->getUser();
                if (!$user->getPassword() || $user->getStatut() === User::STATUS_INACTIVE)
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login_creation_pass')));
            }
        }
    }

}