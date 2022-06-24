<?php

namespace App\EventListener;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $user->setLastLoginAt(new DateTimeImmutable());
        $this->requestStack->getSession()->set('_security.territory', $user->getTerritory());
        // Persist the data to database.
        $this->em->persist($user);
        $this->em->flush();
    }

}