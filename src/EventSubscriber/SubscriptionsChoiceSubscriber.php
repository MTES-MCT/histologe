<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SubscriptionsChoiceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(
        RequestEvent $event,
    ): void {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }
        if (!$user->hasDoneSubscriptionsChoice() && $user->isAloneInPartner(null)) {
            $user->setHasDoneSubscriptionsChoice(true);
            $this->em->flush();
        }
    }
}
